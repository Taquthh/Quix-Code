<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Pemutus sirkuit gateway WhatsApp (T5-10). Setelah N kegagalan beruntun sirkuit terbuka selama M menit:
 * selama itu WhatsApp tidak dicoba dan notifikasi langsung ke email. Sukses mereset hitungan.
 */
class WhatsappCircuitBreaker
{
    private const FAILURES = 'whatsapp-breaker:failures';

    private const OPEN_UNTIL = 'whatsapp-breaker:open-until';

    public function isOpen(): bool
    {
        return Cache::has(self::OPEN_UNTIL);
    }

    public function recordSuccess(): void
    {
        Cache::forget(self::FAILURES);
        Cache::forget(self::OPEN_UNTIL);
    }

    public function recordFailure(): void
    {
        $threshold = max(1, (int) config('quix.whatsapp.breaker_threshold', 3));
        $minutes = max(1, (int) config('quix.whatsapp.breaker_minutes', 10));

        Cache::add(self::FAILURES, 0, now()->addMinutes($minutes * 6));
        $failures = (int) Cache::increment(self::FAILURES);

        if ($failures >= $threshold && ! $this->isOpen()) {
            Cache::put(self::OPEN_UNTIL, now()->addMinutes($minutes)->timestamp, now()->addMinutes($minutes));
            Log::critical('Gateway WhatsApp gagal berulang; sirkuit dibuka, notifikasi dialihkan ke email.', ['failures' => $failures, 'minutes' => $minutes]);
        }
    }
}
