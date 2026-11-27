<?php

namespace App\Support;

use App\Enums\Currency;
use App\Models\ExchangeRate;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

/**
 * Konversi estimasi harga di etalase publik (T6-03, T6-09).
 * Selalu menandai hasil sebagai estimasi beserta waktu pembaruan kurs.
 */
class PublicCurrencyEstimator
{
    public const SESSION_KEY = 'public_currency';

    public static function selectedCurrency(?Request $request = null): Currency
    {
        $req = $request ?? request();
        $raw = $req->session()->get(self::SESSION_KEY);

        if (is_string($raw)) {
            $currency = Currency::tryFrom($raw);
            if ($currency !== null) {
                return $currency;
            }
        }

        return Currency::IDR;
    }

    /**
     * @return array{
     *     money: Money,
     *     is_estimate: bool,
     *     fetched_at: CarbonInterface|null,
     *     is_stale: bool
     * }
     */
    public static function formatAmount(Money $baseMoney, Currency $target): array
    {
        if ($target === $baseMoney->currency) {
            return [
                'money' => $baseMoney,
                'is_estimate' => false,
                'fetched_at' => null,
                'is_stale' => false,
            ];
        }

        $staleHours = max(1, (int) config('quix.exchange_rate.stale_hours', 48));
        $rateRecord = ExchangeRate::latestFor($baseMoney->currency, $target);

        if ($rateRecord !== null) {
            $converted = DisplayCurrency::convert($baseMoney, $target, (string) $rateRecord->rate);

            if ($converted !== null) {
                $isStale = $rateRecord->fetched_at === null
                    || $rateRecord->fetched_at->lt(now()->subHours($staleHours));

                return [
                    'money' => $converted,
                    'is_estimate' => true,
                    'fetched_at' => $rateRecord->fetched_at,
                    'is_stale' => $isStale,
                ];
            }
        }

        $configuredFallback = (float) config('quix.exchange_rate.default_idr_per_usd', 16200);
        $fallbackIdrPerUsd = is_finite($configuredFallback) && $configuredFallback >= 1 && $configuredFallback <= 10_000_000
            ? $configuredFallback
            : 16200.0;
        $fallbackRate = $baseMoney->currency === Currency::IDR
            ? number_format(1 / $fallbackIdrPerUsd, 8, '.', '')
            : number_format($fallbackIdrPerUsd, 8, '.', '');

        $fallbackMoney = DisplayCurrency::convert($baseMoney, $target, $fallbackRate) ?? $baseMoney;

        return [
            'money' => $fallbackMoney,
            'is_estimate' => true,
            'fetched_at' => null,
            'is_stale' => true,
        ];
    }
}
