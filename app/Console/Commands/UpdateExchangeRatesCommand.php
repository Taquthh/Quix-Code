<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;

/** Perbarui kurs publik dari API atau nilai manual, lalu pantau kesegarannya (T6-09). */
class UpdateExchangeRatesCommand extends Command
{
    protected $signature = 'rates:update {--rate= : Input manual nilai 1 USD dalam IDR, mis. 16250}';

    protected $description = 'Perbarui kurs estimasi IDR/USD dan kirim peringatan bila kurs usang > 48 jam';

    public function handle(ExchangeRateService $service): int
    {
        $manualRate = $this->option('rate');

        if (is_string($manualRate) && trim($manualRate) !== '') {
            if (! is_numeric($manualRate) || (float) $manualRate <= 0) {
                $this->error('Nilai --rate harus angka positif.');

                return self::FAILURE;
            }

            $record = $service->storeIdrPerUsd((float) $manualRate);
            $this->info("Kurs manual disimpan (1 USD = {$manualRate} IDR, fetched_at={$record->fetched_at?->toIso8601String()}).");

            return self::SUCCESS;
        }

        $updated = $service->updateFromConfiguredSource();

        if ($updated !== null) {
            $this->info("Kurs berhasil diperbarui dari API ({$updated->fetched_at?->toIso8601String()}).");
        }

        $fresh = $service->checkFreshnessAndAlert();

        if (! $fresh) {
            $this->warn('Kurs belum tersedia atau usang melebihi batas waktu; peringatan admin telah dikirim.');
        }

        return self::SUCCESS;
    }
}
