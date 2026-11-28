<?php

return [

    /*
    | Kebijakan revisi (blueprint Bagian 12, keputusan 13): putaran revisi gratis per tahapan (milestone).
    | Revisi di atas batas ini, atau permintaan fitur baru, menjadi Change Request berbayar (T7-05).
    */
    'free_revision_rounds' => (int) env('QUIX_FREE_REVISION_ROUNDS', 2),

    /*
    | Versi teks persetujuan WhatsApp (T4-08). Naikkan bila teks persetujuan berubah;
    | persetujuan versi lama tetap tercatat sebagai riwayat.
    */
    'whatsapp_consent_version' => env('QUIX_WHATSAPP_CONSENT_VERSION', '1'),

    /*
    | Kontak dukungan yang tampil di halaman Bantuan portal (T4-13). Kosongkan bila tidak dipakai;
    | chat proyek tetap menjadi kanal utama. WhatsApp berupa nomor internasional tanpa tanda plus, mis. 628123456789.
    */
    'support' => [
        'email' => env('QUIX_SUPPORT_EMAIL'),
        'whatsapp' => env('QUIX_SUPPORT_WHATSAPP'),
    ],

    /*
    | Webhook GitHub (Tahap 5). Hanya push ke cabang ini yang memperbarui progres.
    | `queue`: webhook berjalan di antrean 'critical' (T5-09); worker WAJIB mendengarkannya,
    | mis. `queue:work --queue=critical,default,notifications,pdf` (contoh Supervisor: deploy/supervisor).
    */
    'webhook' => [
        'branch' => env('QUIX_WEBHOOK_BRANCH', 'main'),
        'queue' => env('QUIX_QUEUE_WEBHOOK', 'critical'),
        'max_body_kb' => (int) env('QUIX_WEBHOOK_MAX_BODY_KB', 5120),
        'purge_after_days' => (int) env('QUIX_WEBHOOK_PURGE_DAYS', 90),
        'message_limit' => 500,
        'max_commits' => 250,
    ],

    /*
    | Antrean terpisah (T5-09): critical = webhook dan pembayaran; default = lainnya;
    | notifications = email dan WhatsApp (T5-10); pdf = pembuatan dokumen. Urutan = prioritas worker.
    */
    'queues' => ['critical', 'default', 'notifications', 'pdf'],

    /* Circuit breaker gateway WhatsApp (T5-10): N gagal beruntun membuka sirkuit selama M menit (email jadi kanal). */
    'whatsapp' => [
        'breaker_threshold' => (int) env('QUIX_WHATSAPP_BREAKER_THRESHOLD', 3),
        'breaker_minutes' => (int) env('QUIX_WHATSAPP_BREAKER_MINUTES', 10),
    ],

    /* Rekonsiliasi harian lewat GitHub API (T5-08). */
    'reconcile' => [
        'lookback_days' => (int) env('QUIX_RECONCILE_LOOKBACK_DAYS', 3),
        'per_page' => 100,
        'max_pages' => 5,
    ],

    /*
    | Aturan otomatis scheduler (T5-11). Semua aksi dicatat sebagai aksi sistem di activity_logs.
    | - invoice_reminder_days: pengingat invoice H-N (jatuh tempo dalam N hari), H0, dan H+N (terlambat N hari).
    | - dp_hold_after_days: proyek menunggu DP dijeda otomatis bila invoice DP belum dibayar selama N hari sejak terbit.
    | - ticket_sla_hours: tiket tanpa balasan tim (admin atau staf) lebih lama dari N jam dilaporkan ke admin.
    */
    'automation' => [
        'invoice_reminder_days' => (int) env('QUIX_INVOICE_REMINDER_DAYS', 3),
        'dp_hold_after_days' => (int) env('QUIX_DP_HOLD_AFTER_DAYS', 14),
        'ticket_sla_hours' => (int) env('QUIX_TICKET_SLA_HOURS', 48),
    ],

    /* Peringatan operasional ke admin (T5-09, T5-08): satu email per jenis dan antrean dalam rentang ini. */
    'alerts' => [
        'throttle_minutes' => (int) env('QUIX_ALERT_THROTTLE_MINUTES', 10),
        'queue_max' => (int) env('QUIX_QUEUE_MAX_PENDING', 200),
        'stale_webhook_hours' => (int) env('QUIX_STALE_WEBHOOK_HOURS', 24),
    ],

    /* Kurs mata uang publik (T6-03, T6-09): hanya estimasi; lebih dari 48 jam memicu peringatan admin. */
    'exchange_rate' => [
        'stale_hours' => (int) env('QUIX_EXCHANGE_RATE_STALE_HOURS', 48),
        'default_idr_per_usd' => (float) env('QUIX_DEFAULT_IDR_PER_USD', 16200),
    ],

    /*
     * Anggota tim untuk halaman Tentang (T6-01, T6-10). Diisi pemilik sebelum rilis, contoh:
     * ['name' => 'Nama', 'role' => ['id' => 'Pendiri & Engineer', 'en' => 'Founder & Engineer'], 'bio' => ['id' => '...', 'en' => '...']].
     * Bila kosong, bagian Tim tidak ditampilkan (bukan placeholder).
     */
    'team' => [],

    /* Kepatuhan UU PDP & dokumen legal publik (T6-08). */
    'legal' => [
        'terms_version' => env('QUIX_TERMS_VERSION', '2026.1'),
        'privacy_version' => env('QUIX_PRIVACY_VERSION', '2026.1'),
        'retention_years' => (int) env('QUIX_DATA_RETENTION_YEARS', 5),
        'dpo_email' => env('QUIX_DPO_EMAIL', 'privacy@quixcode.my.id'),
    ],
];
