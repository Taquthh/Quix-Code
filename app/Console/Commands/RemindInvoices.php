<?php

namespace App\Console\Commands;

use App\Services\AutomationService;
use Illuminate\Console\Command;

class RemindInvoices extends Command
{
    protected $signature = 'invoices:remind';

    protected $description = 'Kirim pengingat invoice belum dibayar: H-3, H0, H+3 (T5-11)';

    public function handle(AutomationService $automation): int
    {
        $count = $automation->remindInvoices();

        $this->info("$count pengingat invoice dikirim.");

        return self::SUCCESS;
    }
}
