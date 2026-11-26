<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Jobs\ProcessGithubPush;
use App\Models\Project;
use App\Models\ProjectGitCommit;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Rekonsiliasi harian (T5-08): membandingkan commit di cabang utama GitHub dengan yang sudah tersimpan,
 * lalu mengantrekan commit yang terlewat sebagai delivery sintetis lewat jalur pemrosesan yang sama
 * (ProcessGithubPush), sehingga semua aturan progres, pagar 100%, dan sanitasi tetap berlaku.
 *
 * Idempoten: id delivery diturunkan dari kumpulan commit yang hilang, dan commit unik per proyek.
 */
class GithubReconciler
{
    /** Status proyek yang masih menerima pembaruan pengerjaan. */
    private const STATUSES = [ProjectStatus::Active, ProjectStatus::OnHold, ProjectStatus::AwaitingFinal];

    /** @return array{projects: int, missing: int, errors: int} */
    public function run(?string $projectId = null): array
    {
        $result = ['projects' => 0, 'missing' => 0, 'errors' => 0];

        $projects = Project::query()
            ->whereNotNull('github_repo')
            ->whereNotNull('webhook_secret')
            ->whereIn('status', array_map(fn (ProjectStatus $s) => $s->value, self::STATUSES))
            ->when($projectId, fn ($query) => $query->whereKey($projectId))
            ->get();

        foreach ($projects as $project) {
            $result['projects']++;

            try {
                $result['missing'] += $this->reconcile($project);
            } catch (Throwable $e) {
                $result['errors']++;
                Log::warning('Rekonsiliasi GitHub gagal untuk satu proyek.', ['project' => $project->id, 'error' => $e->getMessage()]);
            }
        }

        return $result;
    }

    /** @return int jumlah commit terlewat yang diantrekan */
    public function reconcile(Project $project): int
    {
        $repo = (string) $project->github_repo;
        $branch = (string) config('quix.webhook.branch');
        $remote = $this->fetchCommits($repo, $branch);

        if ($remote === []) {
            return 0;
        }

        $known = ProjectGitCommit::query()
            ->where('project_id', $project->id)
            ->whereIn('commit_hash', array_column($remote, 'id'))
            ->pluck('commit_hash')
            ->all();

        $missing = array_values(array_filter($remote, fn (array $c) => ! in_array($c['id'], $known, true)));

        if ($missing === []) {
            return 0;
        }

        $deliveryId = 'reconcile-'.substr(sha1($project->id.'|'.implode(',', array_column($missing, 'id'))), 0, 32);

        $delivery = WebhookDelivery::query()->firstOrCreate(
            ['delivery_id' => $deliveryId],
            [
                'project_id' => $project->id,
                'event' => 'push',
                'status' => 'received',
                'payload' => [
                    'ref' => 'refs/heads/'.$branch,
                    'before' => '',
                    'after' => $missing[0]['id'],
                    'forced' => false,
                    'repository' => ['full_name' => $repo],
                    'commits' => $missing,
                ],
                'received_at' => now(),
                'signature_valid' => true,
                'purge_after' => now()->addDays((int) config('quix.webhook.purge_after_days')),
            ],
        );

        if (! $delivery->wasRecentlyCreated) {
            if ($delivery->status !== 'failed') {
                return 0;
            }

            $delivery->forceFill(['status' => 'received', 'error' => null])->save();
        }

        ProcessGithubPush::dispatch($delivery->id);

        return count($missing);
    }

    /**
     * Commit terbaru di cabang utama, format sama dengan payload push.
     *
     * @return list<array{id: string, message: string, timestamp: string, author_email: string}>
     */
    private function fetchCommits(string $repo, string $branch): array
    {
        $token = (string) config('services.github.token');

        if ($token === '') {
            throw new RuntimeException('GITHUB_TOKEN belum diisi.');
        }

        [$owner, $name] = array_pad(explode('/', $repo, 2), 2, '');
        $url = rtrim((string) config('services.github.api_url'), '/').'/repos/'.rawurlencode($owner).'/'.rawurlencode($name).'/commits';
        $perPage = (int) config('quix.reconcile.per_page');
        $since = now()->subDays((int) config('quix.reconcile.lookback_days'))->utc()->format('Y-m-d\TH:i:s\Z');
        $commits = [];

        for ($page = 1; $page <= (int) config('quix.reconcile.max_pages'); $page++) {
            $response = Http::withToken($token)
                ->acceptJson()
                ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28'])
                ->timeout(15)
                ->get($url, ['sha' => $branch, 'since' => $since, 'per_page' => $perPage, 'page' => $page]);

            if ($response->failed()) {
                throw new RuntimeException("GitHub membalas HTTP {$response->status()} untuk {$repo}.");
            }

            $items = $response->json();

            if (! is_array($items) || $items === []) {
                break;
            }

            foreach ($items as $item) {
                $id = strtolower((string) data_get($item, 'sha', ''));

                if (! preg_match('/^[0-9a-f]{40}$|^[0-9a-f]{64}$/', $id)) {
                    continue;
                }

                $commits[] = [
                    'id' => $id,
                    'message' => mb_substr((string) data_get($item, 'commit.message', ''), 0, 2000),
                    'timestamp' => (string) (data_get($item, 'commit.committer.date') ?? data_get($item, 'commit.author.date', '')),
                    'author_email' => (string) data_get($item, 'commit.author.email', ''),
                ];
            }

            if (count($items) < $perPage) {
                break;
            }
        }

        return $commits;
    }
}
