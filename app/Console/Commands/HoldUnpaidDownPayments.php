<?php

namespace App\Console\Commands;

use App\Services\AutomationService;
use Illuminate\Console\Command;

class HoldUnpaidDownPayments extends Command
{
    protected $signature = 'projects:hold-unpaid-dp';

    protected $description = 'Jeda proyek menunggu DP yang invoice DP-nya belum dibayar melewati batas hari (T5-11)';

    public function handle(AutomationService $automation): int
    {
        $count = $automation->holdUnpaidDownPayments();

        $this->info("$count proyek dijeda.");

        return self::SUCCESS;
    }
}
