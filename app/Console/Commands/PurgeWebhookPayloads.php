<?php

namespace App\Console\Commands;

use App\Services\AutomationService;
use Illuminate\Console\Command;

class PurgeWebhookPayloads extends Command
{
    protected $signature = 'webhooks:purge';

    protected $description = 'Bersihkan payload webhook yang sudah lewat masa simpan, baris delivery tetap ada (T5-11)';

    public function handle(AutomationService $automation): int
    {
        $count = $automation->purgeWebhookPayloads();

        $this->info("$count payload webhook dibersihkan.");

        return self::SUCCESS;
    }
}
