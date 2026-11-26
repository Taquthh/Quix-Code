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
];
