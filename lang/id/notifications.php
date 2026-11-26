<?php

return [
    'title' => 'Notifikasi',
    'subtitle' => 'Kabar terbaru tentang proyek, tagihan, dan permintaan Anda.',
    'bell_label' => '{0} Notifikasi, tidak ada yang belum dibaca|{1} Notifikasi, 1 belum dibaca|[2,*] Notifikasi, :count belum dibaca',
    'unread' => 'Belum dibaca',
    'mark_read' => 'Tandai dibaca',
    'mark_all_read' => 'Tandai semua dibaca',
    'empty' => 'Belum ada notifikasi',
    'empty_hint' => 'Kabar tentang penawaran, tagihan, dan balasan tim akan muncul di sini.',
    'unknown' => 'Pembaruan baru',

    'items' => [
        'quotation_sent' => [
            'title' => 'Penawaran baru v:version',
            'body' => 'Proyek :project: total :amount. Silakan ditinjau.',
        ],
        'quotation_expiring' => [
            'title' => 'Penawaran segera berakhir',
            'body' => 'Penawaran v:version untuk :project hampir berakhir.',
        ],
        'dp_invoice' => [
            'title' => 'Invoice DP :number terbit',
            'body' => 'Proyek :project: :amount. Pengerjaan dimulai setelah DP diverifikasi.',
        ],
        'invoice_issued' => [
            'title' => 'Invoice :number terbit',
            'body' => 'Proyek :project: :amount.',
        ],
        'payment_verified' => [
            'title' => 'Pembayaran invoice :number diterima',
            'body' => 'Proyek :project: :amount sudah diverifikasi.',
        ],
        'payment_rejected' => [
            'title' => 'Bukti bayar invoice :number ditolak',
            'body' => 'Alasan: :reason. Unggah ulang bukti yang benar.',
        ],
        'ticket_replied' => [
            'title' => 'Balasan baru untuk ":subject"',
            'body' => 'Tim membalas permintaan Anda di :project.',
        ],
    ],
];
