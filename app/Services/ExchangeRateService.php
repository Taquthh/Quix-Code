<?php

namespace App\Services;

use App\Enums\Currency;
use App\Models\ExchangeRate;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Pembaruan dan pemeriksaan kesegaran kurs IDR <-> USD untuk estimasi tampilan publik (T6-09).
 */
class ExchangeRateService
{
    public function __construct(private readonly OpsAlerts $alerts) {}

    /**
     * Ambil kurs dari endpoint JSON terkonfigurasi.
     * Mendukung payload berbentuk {"rates": {"USD": 0.0000615}} atau {"idr_per_usd": 16250}.
     */
    public function updateFromConfiguredSource(): ?ExchangeRate
    {
        $url = config('services.exchange_rate.url');

        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        try {
            $request = Http::acceptJson()->timeout(max(2, (int) config('services.exchange_rate.timeout', 8)));
            $key = config('services.exchange_rate.key');

            if (is_string($key) && trim($key) !== '') {
                $request = $request->withToken($key);
            }

            $response = $request->get($url);

            if (! $response->successful()) {
                Log::warning('Pembaruan kurs gagal dari API.', ['status' => $response->status()]);

                return null;
            }

            $data = $response->json();

            if (! is_array($data)) {
                return null;
            }

            $idrPerUsd = data_get($data, 'idr_per_usd');

            if (is_numeric($idrPerUsd) && (float) $idrPerUsd > 0) {
                return $this->storeIdrPerUsd((float) $idrPerUsd);
            }

            $usdPerIdr = data_get($data, 'rates.USD');

            if (is_numeric($usdPerIdr) && (float) $usdPerIdr > 0) {
                return $this->storeIdrPerUsd(1 / (float) $usdPerIdr);
            }
        } catch (Throwable $e) {
            Log::warning('Pembaruan kurs melempar pengecualian.', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /** Simpan kurs dua arah (IDR -> USD dan USD -> IDR) secara atomik. */
    public function storeIdrPerUsd(float|int|string $idrPerUsd, ?CarbonInterface $fetchedAt = null): ExchangeRate
    {
        $numeric = (float) $idrPerUsd;

        if ($numeric <= 0) {
            throw new InvalidArgumentException('Nilai kurs harus lebih besar dari nol.');
        }

        $timestamp = $fetchedAt ?? now();
        $usdPerIdrString = number_format(1 / $numeric, 8, '.', '');
        $idrPerUsdString = number_format($numeric, 8, '.', '');

        return DB::transaction(function () use ($usdPerIdrString, $idrPerUsdString, $timestamp): ExchangeRate {
            $idrToUsd = ExchangeRate::query()->create([
                'base_currency' => Currency::IDR,
                'target_currency' => Currency::USD,
                'rate' => $usdPerIdrString,
                'fetched_at' => $timestamp,
            ]);

            ExchangeRate::query()->create([
                'base_currency' => Currency::USD,
                'target_currency' => Currency::IDR,
                'rate' => $idrPerUsdString,
                'fetched_at' => $timestamp,
            ]);

            return $idrToUsd;
        });
    }

    /** Kirim peringatan ke admin bila kurs belum tersedia atau usang melebihi batas jam. */
    public function checkFreshnessAndAlert(): bool
    {
        $staleHours = max(1, (int) config('quix.exchange_rate.stale_hours', 48));
        $latest = ExchangeRate::latestFor(Currency::IDR, Currency::USD);

        if ($latest === null || $latest->fetched_at === null) {
            $this->alerts->staleExchangeRate($staleHours);

            return false;
        }

        if ($latest->fetched_at->lt(now()->subHours($staleHours))) {
            $hoursOld = (int) $latest->fetched_at->diffInHours(now());
            $this->alerts->staleExchangeRate(max($staleHours, $hoursOld));

            return false;
        }

        return true;
    }
}
