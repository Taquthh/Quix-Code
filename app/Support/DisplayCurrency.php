<?php

namespace App\Support;

use App\Enums\Currency;
use App\Models\ExchangeRate;
use App\Models\User;

/**
 * Estimasi nilai dalam mata uang tampilan pilihan pengguna (users.preferred_currency).
 * Hanya perkiraan: angka mengikat (quotation, invoice, pembayaran) selalu memakai mata uang proyek.
 */
class DisplayCurrency
{
    /** Estimasi bila mata uang pilihan berbeda dan kurs tersedia; selain itu null. */
    public static function estimate(Money $money, ?User $user): ?Money
    {
        $target = $user?->preferred_currency;

        if (! $target instanceof Currency || $target === $money->currency) {
            return null;
        }

        $rate = ExchangeRate::latestFor($money->currency, $target);

        return $rate === null ? null : self::convert($money, $target, (string) $rate->rate);
    }

    /** Konversi dengan aritmetika bilangan bulat (kurs berpresisi 8 desimal), dibulatkan half up. */
    public static function convert(Money $money, Currency $target, string $rate): ?Money
    {
        if (preg_match('/^(\d+)(?:\.(\d{1,8}))?$/D', $rate, $m) !== 1) {
            return null;
        }

        $scaled = (int) ($m[1].str_pad($m[2] ?? '', 8, '0'));

        if ($scaled <= 0) {
            return null;
        }

        $amount = abs($money->amount);
        $multiplier = $scaled * $target->minorFactor();

        if ($amount !== 0 && $amount > intdiv(PHP_INT_MAX, $multiplier)) {
            return null;
        }

        $denominator = $money->currency->minorFactor() * 100_000_000;
        $product = $amount * $multiplier;
        $converted = intdiv($product, $denominator);

        if ($product % $denominator >= intdiv($denominator + 1, 2)) {
            $converted++;
        }

        return Money::of($money->amount < 0 ? -$converted : $converted, $target);
    }
}
