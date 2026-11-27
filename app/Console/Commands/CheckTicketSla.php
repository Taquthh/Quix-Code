<?php

namespace App\Console\Commands;

use App\Services\AutomationService;
use Illuminate\Console\Command;

class CheckTicketSla extends Command
{
    protected $signature = 'tickets:check-sla';

    protected $description = 'Laporkan ke admin tiket yang belum dibalas tim melewati batas jam SLA (T5-11)';

    public function handle(AutomationService $automation): int
    {
        $count = $automation->flagUnansweredTickets();

        $this->info("$count tiket dilaporkan.");

        return self::SUCCESS;
    }
}
