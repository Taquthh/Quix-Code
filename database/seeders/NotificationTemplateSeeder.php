<?php

namespace Database\Seeders;

use App\Services\NotificationTemplateService;
use Illuminate\Database\Seeder;

/** Template notifikasi bawaan ID/EN untuk email dan WhatsApp (T5-13). Idempoten; dipanggil juga oleh seeder produksi (T1-13). */
class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        app(NotificationTemplateService::class)->seedDefaults();
    }
}
