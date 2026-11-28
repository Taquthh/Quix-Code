<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Services\GithubPushProcessor;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Throwable;

/** Memproses delivery push di antrean, satu per proyek dalam satu waktu (T5-01, T5-06). */
class ProcessGithubPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 8;

    public int $maxExceptions = 4;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    public function __construct(public int $deliveryId)
    {
        $this->onQueue((string) config('quix.webhook.queue'));
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [5, 15, 60, 300];
    }

    public function handle(GithubPushProcessor $processor): void
    {
        $delivery = WebhookDelivery::query()->find($this->deliveryId);

        if ($delivery === null || $delivery->status === 'processed' || $delivery->status === 'ignored') {
            return;
        }

        $lock = Cache::lock('github-push:'.($delivery->project_id ?? 'none'), 120);

        try {
            $lock->block(15);
        } catch (LockTimeoutException) {
            $this->release(5);

            return;
        }

        try {
            $processor->process($delivery);
        } catch (Throwable $e) {
            $processor->fail($delivery, $e);

            throw $e;
        } finally {
            $lock->release();
        }
    }

    public function failed(Throwable $e): void
    {
        $delivery = WebhookDelivery::query()->find($this->deliveryId);

        if ($delivery !== null) {
            app(GithubPushProcessor::class)->fail($delivery, $e);
        }
    }
}
