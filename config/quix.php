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

    /* Rekonsiliasi harian lewat GitHub API (T5-08). */
    'reconcile' => [
        'lookback_days' => (int) env('QUIX_RECONCILE_LOOKBACK_DAYS', 3),
        'per_page' => 100,
        'max_pages' => 5,
    ],

    /* Peringatan operasional ke admin (T5-09): satu email per jenis dan antrean dalam rentang ini. */
    'alerts' => [
        'throttle_minutes' => (int) env('QUIX_ALERT_THROTTLE_MINUTES', 10),
        'queue_max' => (int) env('QUIX_QUEUE_MAX_PENDING', 200),
    ],
];
