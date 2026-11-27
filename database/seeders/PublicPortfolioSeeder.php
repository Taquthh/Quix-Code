<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\CaseStudy;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder idempoten berisi 3 studi kasus dwibahasa (ID/EN) untuk etalase publik (T6-10).
 */
class PublicPortfolioSeeder extends Seeder
{
    public function run(): void
    {
        $showcaseClient = User::query()->firstOrCreate(
            ['email' => 'showcase@quixcode.test'],
            [
                'name' => 'Quix Code Showcase Client',
                'password' => Str::random(40),
                'role' => UserRole::Client,
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $studies = [
            [
                'slug' => 'kipay-qris-payment-gateway-sandbox',
                'project_title' => 'Kipay QRIS Payment Gateway Simulator',
                'title' => [
                    'id' => 'Kipay — Simulator Payment Gateway QRIS & E-Wallet',
                    'en' => 'Kipay — QRIS Payment Gateway & E-Wallet Sandbox',
                ],
                'summary' => [
                    'id' => 'Platform simulasi pembayaran QRIS real-time dengan pemindaian kamera langsung, manajemen merchant, callback webhook bertanda tangan, dan notifikasi WhatsApp otomatis.',
                    'en' => 'Real-time QRIS payment simulation platform featuring live camera QR scanning, merchant settlement dashboards, signed webhook callbacks, and automated WhatsApp alerts.',
                ],
                'tech_stack' => ['Laravel 13', 'Livewire 4', 'Tailwind CSS', 'MySQL', 'Redis'],
            ],
            [
                'slug' => 'fjs-auto-service-workshop-erp',
                'project_title' => 'FJS Auto Service Operations & POS',
                'title' => [
                    'id' => 'FJS Auto Service — Sistem Operasional Bengkel & POS',
                    'en' => 'FJS Auto Service — Workshop Operations & POS Suite',
                ],
                'summary' => [
                    'id' => 'Sistem manajemen antrean servis kendaraan, kontrol stok suku cadang, estimasi biaya otomatis, dan pencetakan nota digital terintegrasi.',
                    'en' => 'End-to-end service bay scheduling, spare-parts inventory tracking, automated repair estimation, and digital receipt generation.',
                ],
                'tech_stack' => ['Laravel 12', 'Livewire', 'Alpine.js', 'MySQL'],
            ],
            [
                'slug' => 'gold-silver-retail-pos-digital-signage',
                'project_title' => 'Precious Metals Retail POS & Digital Signage',
                'title' => [
                    'id' => 'Sistem POS Ritel Perhiasan & Digital Signage Harga Emas',
                    'en' => 'Jewelry Retail POS & Real-Time Gold Price Digital Signage',
                ],
                'summary' => [
                    'id' => 'Aplikasi kasir perhiasan emas dan perak dengan pelacakan berat gram presisi, papan harga digital real-time di toko, dan program loyalitas pelanggan.',
                    'en' => 'Precious-metal retail POS with gram-level inventory tracking, live in-store digital price boards, and customer loyalty membership.',
                ],
                'tech_stack' => ['Laravel', 'Livewire', 'Tailwind CSS', 'MySQL'],
            ],
        ];

        foreach ($studies as $index => $item) {
            $project = Project::query()->firstOrCreate(
                ['title' => $item['project_title']],
                [
                    'client_id' => $showcaseClient->id,
                    'description' => $item['summary']['id'],
                    'lead_source' => 'website',
                    'currency' => 'IDR',
                ],
            );

            if ($project->status !== ProjectStatus::Closed) {
                $project->forceFill([
                    'status' => ProjectStatus::Closed,
                    'progress_percent' => 100,
                    'completed_at' => now()->subDays(30 - $index * 5),
                ])->saveQuietly();
            }

            CaseStudy::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'project_id' => $project->id,
                    'title' => $item['title'],
                    'summary' => $item['summary'],
                    'tech_stack' => $item['tech_stack'],
                    'is_published' => true,
                    'published_at' => now()->subDays(20 - $index * 3),
                ],
            );
        }
    }
}
