<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessGithubPush;
use App\Models\Project;
use App\Models\WebhookDelivery;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Endpoint webhook GitHub per proyek: POST /webhooks/github/{project} (T5-01 s.d. T5-03).
 *
 * Urutan: tanda tangan HMAC dari raw body (hash_equals) → idempotensi X-GitHub-Delivery →
 * validasi repo & cabang → antrekan job lalu balas 202. Tidak ada pemrosesan berat di request.
 * Semua penolakan tanda tangan memakai satu jawaban 401 agar keberadaan proyek tidak bocor.
 */
class GithubWebhookController extends Controller
{
    public function __invoke(Request $request, string $project): JsonResponse
    {
        $body = $request->getContent();

        if (strlen($body) > (int) config('quix.webhook.max_body_kb') * 1024) {
            return response()->json(['message' => 'Payload too large.'], 413);
        }

        $model = Project::query()->find($project);

        if (! $this->signatureIsValid($request, $model, $body)) {
            $this->recordRejected($request, $model);
            Log::warning('Webhook GitHub ditolak: tanda tangan tidak valid.', ['project' => $model?->id, 'ip' => $request->ip()]);

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $deliveryId = (string) $request->header('X-GitHub-Delivery', '');
        $event = Str::limit((string) $request->header('X-GitHub-Event', ''), 50, '');
        $payload = json_decode($body, true);

        if (! preg_match('/^[A-Za-z0-9-]{8,64}$/', $deliveryId) || $event === '' || ! is_array($payload)) {
            return response()->json(['message' => 'Bad request.'], 400);
        }

        if ($event === 'ping') {
            $this->record($model, $deliveryId, $event, 'processed', ['zen' => 'ping'], null);

            return response()->json(['message' => 'pong'], 200);
        }

        if ($event !== 'push') {
            $this->record($model, $deliveryId, $event, 'ignored', [], 'event_ignored');

            return response()->json(['message' => 'Event ignored.'], 202);
        }

        $reason = $this->ignoreReason($model, $payload);
        $delivery = $this->record(
            $model,
            $deliveryId,
            $event,
            $reason ? 'ignored' : 'received',
            $reason ? Arr::only($payload, ['ref']) : $this->reduce($payload),
            $reason,
        );

        if ($delivery === null) {
            return $this->handleDuplicate($model, $deliveryId);
        }

        if ($reason === null) {
            ProcessGithubPush::dispatch($delivery->id)->onQueue((string) config('quix.webhook.queue'));
        }

        return response()->json(['message' => $reason ? 'Ignored.' : 'Accepted.'], 202);
    }

    private function signatureIsValid(Request $request, ?Project $project, string $body): bool
    {
        // Secret yang tidak bisa didekripsi (APP_KEY berganti) diperlakukan sebagai tidak ada.
        $secret = $project === null ? null : rescue(fn () => $project->webhook_secret, null, false);

        $signature = (string) $request->header('X-Hub-Signature-256', '');
        $expected = 'sha256='.hash_hmac('sha256', $body, (string) ($secret ?: Str::random(32)));
        $matches = hash_equals($expected, $signature);

        $repo = $project === null ? null : $project->github_repo;

        return $matches && filled($secret) && filled($repo);
    }

    /** @param array<string, mixed> $payload */
    private function ignoreReason(Project $project, array $payload): ?string
    {
        if (strcasecmp((string) data_get($payload, 'repository.full_name', ''), (string) $project->github_repo) !== 0) {
            return 'repo_mismatch';
        }

        if (($payload['ref'] ?? null) !== 'refs/heads/'.config('quix.webhook.branch') || ! empty($payload['deleted'])) {
            return 'branch_ignored';
        }

        return null;
    }

    /**
     * Kirim ulang dari GitHub memakai delivery id yang sama. Delivery yang sebelumnya gagal diantrekan lagi
     * (aman karena pemrosesan idempoten); selain itu tidak ada efek.
     */
    private function handleDuplicate(Project $project, string $deliveryId): JsonResponse
    {
        $existing = WebhookDelivery::query()->where('delivery_id', $deliveryId)->first();

        if ($existing && $existing->status === 'failed' && $existing->signature_valid
            && $existing->project_id === $project->id && $existing->event === 'push') {
            $existing->forceFill(['status' => 'received', 'error' => null])->save();
            ProcessGithubPush::dispatch($existing->id)->onQueue((string) config('quix.webhook.queue'));

            return response()->json(['message' => 'Requeued.'], 202);
        }

        return response()->json(['message' => 'Duplicate delivery.'], 202);
    }

    /** @param array<string, mixed> $payload */
    private function record(Project $project, string $deliveryId, string $event, string $status, array $payload, ?string $note): ?WebhookDelivery
    {
        try {
            return WebhookDelivery::query()->create([
                'project_id' => $project->id,
                'delivery_id' => $deliveryId,
                'event' => $event,
                'status' => $status,
                'payload' => $payload,
                'received_at' => now(),
                'processed_at' => $status === 'received' ? null : now(),
                'error' => $note,
                'signature_valid' => true,
                'purge_after' => now()->addDays((int) config('quix.webhook.purge_after_days')),
            ]);
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }

    /** Percobaan palsu dicatat tanpa menyimpan isi payload yang tidak terpercaya. */
    private function recordRejected(Request $request, ?Project $project): void
    {
        try {
            WebhookDelivery::query()->create([
                'project_id' => $project?->id,
                'delivery_id' => 'rejected-'.Str::ulid(),
                'event' => Str::limit((string) $request->header('X-GitHub-Event', 'unknown'), 50, ''),
                'status' => 'failed',
                'payload' => ['reason' => 'invalid_signature', 'ip' => $request->ip()],
                'received_at' => now(),
                'processed_at' => now(),
                'error' => 'invalid_signature',
                'signature_valid' => false,
                'purge_after' => now()->addDays((int) config('quix.webhook.purge_after_days')),
            ]);
        } catch (Throwable) {
            // pencatatan tidak boleh mengubah jawaban 401
        }
    }

    /**
     * Simpan hanya bagian payload yang dipakai (hemat ruang, tanpa data pribadi lain).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function reduce(array $payload): array
    {
        $commits = (array) ($payload['commits'] ?? []);

        if ($commits === [] && is_array($payload['head_commit'] ?? null)) {
            $commits = [$payload['head_commit']];
        }

        return [
            'ref' => (string) $payload['ref'],
            'before' => (string) ($payload['before'] ?? ''),
            'after' => (string) ($payload['after'] ?? ''),
            'forced' => (bool) ($payload['forced'] ?? false),
            'repository' => ['full_name' => (string) data_get($payload, 'repository.full_name')],
            'commits' => collect($commits)
                ->filter(fn ($c) => is_array($c))
                ->take((int) config('quix.webhook.max_commits'))
                ->map(fn (array $c) => [
                    'id' => (string) ($c['id'] ?? ''),
                    'message' => Str::limit((string) ($c['message'] ?? ''), 2000, ''),
                    'timestamp' => (string) ($c['timestamp'] ?? ''),
                    'author_email' => (string) data_get($c, 'author.email', ''),
                ])
                ->values()
                ->all(),
        ];
    }
}
