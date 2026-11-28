<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\ProjectGitCommit;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Support\ActivityLogger;
use App\Support\CommitMessage;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Memproses satu delivery push GitHub yang sudah lolos validasi (T5-04 s.d. T5-07).
 *
 * Idempoten: commit unik per (project_id, commit_hash) dan progres hanya naik. Progres hanya
 * dihitung dari commit yang baru tersimpan, sehingga replay tidak menaikkan lagi progres yang
 * sengaja diturunkan admin. Pemanggil wajib memegang lock per proyek (lihat ProcessGithubPush).
 */
class GithubPushProcessor
{
    public function process(WebhookDelivery $delivery): void
    {
        $delivery->increment('attempts');

        $project = $delivery->project_id ? Project::query()->find($delivery->project_id) : null;

        if ($project === null) {
            $this->finish($delivery, 'ignored', 'project_missing');

            return;
        }

        /** @var array<string, mixed> $payload */
        $payload = (array) $delivery->payload;
        $branch = $this->branchOf($payload);
        $commits = $this->commitsOf($payload);

        $note = DB::transaction(function () use ($project, $commits, $branch): ?string {
            $project = Project::query()->whereKey($project->getKey())->lockForUpdate()->firstOrFail();

            $staff = $this->staffByEmail($commits);
            $tags = [];
            $invalid = false;

            foreach ($commits as $commit) {
                $parsed = CommitMessage::parse($commit['message']);
                $invalid = $invalid || $parsed['invalid_progress'];
                $email = $commit['email'] !== '' ? $commit['email'] : null;

                $record = ProjectGitCommit::query()->firstOrCreate(
                    ['project_id' => $project->id, 'commit_hash' => $commit['id']],
                    [
                        'commit_message' => $parsed['message'],
                        'progress_recorded' => $parsed['progress'],
                        'pushed_at' => $commit['at'],
                        'author_email' => $email,
                        'author_id' => $email ? ($staff[strtolower($email)] ?? null) : null,
                        'branch' => $branch,
                    ],
                );

                if ($record->wasRecentlyCreated && $parsed['progress'] !== null) {
                    $tags[] = $parsed['progress'];
                }
            }

            $note = $invalid ? 'progress_out_of_range_ignored' : null;

            if ($tags === []) {
                return $note;
            }

            if (! $project->acceptsAutomaticProgress()) {
                return 'progress_ignored_status';
            }

            $this->applyProgress($project, max($tags));

            return $note;
        });

        $this->finish($delivery, 'processed', $note);
    }

    /** Progres hanya naik; 100% ditahan di 99% sampai admin mengonfirmasi (T5-05). */
    private function applyProgress(Project $project, int $requested): void
    {
        $old = (int) $project->progress_percent;
        $new = max($old, min($requested, 99));
        $dirty = false;

        if ($requested >= 100 && $old < 100 && $project->progress_pending_confirm_at === null) {
            $project->progress_pending_confirm_at = now();
            $dirty = true;
        }

        if ($new !== $old) {
            $project->progress_percent = $new;
            $dirty = true;
        }

        if (! $dirty) {
            return;
        }

        $project->save();

        if ($new !== $old) {
            $project->completeReachedMilestones();
            ActivityLogger::log(ActivityAction::ProgressChanged, $project, [
                'old' => $old,
                'new' => $new,
                'source' => 'github',
            ]);
        }
    }

    public function fail(WebhookDelivery $delivery, Throwable $e): void
    {
        $delivery->forceFill([
            'status' => 'failed',
            'error' => mb_substr($e->getMessage(), 0, 1000),
        ])->save();
    }

    private function finish(WebhookDelivery $delivery, string $status, ?string $note): void
    {
        $delivery->forceFill([
            'status' => $status,
            'processed_at' => now(),
            'error' => $note,
        ])->save();
    }

    /** @param array<string, mixed> $payload */
    private function branchOf(array $payload): string
    {
        return preg_replace('#^refs/heads/#', '', (string) ($payload['ref'] ?? '')) ?: (string) config('quix.webhook.branch');
    }

    /**
     * Commit valid, urut menurut waktu push (lama ke baru) agar urutan kirim terbalik tidak berpengaruh.
     *
     * @param  array<string, mixed>  $payload
     * @return list<array{id: string, message: string, email: string, at: CarbonInterface}>
     */
    private function commitsOf(array $payload): array
    {
        $raw = (array) ($payload['commits'] ?? []);
        $items = [];

        foreach ($raw as $index => $commit) {
            $id = strtolower((string) ($commit['id'] ?? ''));
            if (! preg_match('/^[0-9a-f]{40}$|^[0-9a-f]{64}$/', $id)) {
                continue;
            }

            try {
                $at = Carbon::parse((string) ($commit['timestamp'] ?? ''));
            } catch (Throwable) {
                $at = now();
            }

            $items[] = [
                'id' => $id,
                'message' => (string) ($commit['message'] ?? ''),
                'email' => mb_substr((string) ($commit['author_email'] ?? ''), 0, 255),
                'at' => $at,
                'order' => $index,
            ];
        }

        usort($items, fn (array $a, array $b) => [$a['at']->getTimestamp(), $a['order']] <=> [$b['at']->getTimestamp(), $b['order']]);

        $result = [];
        foreach ($items as $item) {
            $result[] = ['id' => $item['id'], 'message' => $item['message'], 'email' => $item['email'], 'at' => $item['at']];
        }

        return $result;
    }

    /**
     * Pemetaan author_email → staf/admin (T5-07). Klien tidak pernah menjadi penulis.
     *
     * @param  list<array{email: string}>  $commits
     * @return array<string, int>
     */
    private function staffByEmail(array $commits): array
    {
        $emails = collect($commits)->pluck('email')->filter()->map(fn ($e) => strtolower($e))->unique()->values();

        if ($emails->isEmpty()) {
            return [];
        }

        return User::query()
            ->whereIn('role', [UserRole::Staff->value, UserRole::Admin->value])
            ->whereIn('email', $emails)
            ->get(['id', 'email'])
            ->mapWithKeys(fn (User $u) => [strtolower($u->email) => $u->id])
            ->all();
    }
}
