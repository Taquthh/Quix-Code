<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\OpsAlertNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Peringatan operasional ke admin aktif (T5-09). Dibatasi satu email per jenis dan antrean dalam rentang
 * config('quix.alerts.throttle_minutes') agar badai kegagalan tidak membanjiri kotak masuk, dan kegagalan
 * mengirim peringatan tidak pernah mengganggu worker.
 */
class OpsAlerts
{
    public function failedJob(string $job, string $queue, Throwable $e): void
    {
        $error = $e::class.': '.Str::limit($e->getMessage(), 200, '…');

        Log::critical('Job gagal permanen: '.$job, ['queue' => $queue, 'error' => $error]);

        $this->send("failed_job:{$queue}", 'failed_job', ['job' => class_basename($job), 'queue' => $queue, 'error' => $error]);
    }

    public function queueBusy(string $connection, string $queue, int $size): void
    {
        Log::warning('Antrean menumpuk: '.$queue, ['connection' => $connection, 'size' => $size]);

        $this->send("queue_busy:{$queue}", 'queue_busy', ['connection' => $connection, 'queue' => $queue, 'size' => $size]);
    }

    /** @param array<string, mixed> $context */
    private function send(string $key, string $kind, array $context): void
    {
        $minutes = max(1, (int) config('quix.alerts.throttle_minutes'));

        if (! Cache::add('ops-alert:'.$key, 1, now()->addMinutes($minutes))) {
            return;
        }

        $admins = User::query()->where('role', UserRole::Admin->value)->where('is_active', true)->get();

        foreach ($admins as $admin) {
            try {
                $admin->notify((new OpsAlertNotification($kind, $context))->locale($admin->locale ?: 'id'));
            } catch (Throwable $e) {
                Log::error('Peringatan operasional gagal dikirim.', ['admin' => $admin->id, 'error' => $e->getMessage()]);
            }
        }
    }
}
