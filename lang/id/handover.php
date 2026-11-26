<?php

return [
    'title' => 'Serah terima & berkas',
    'back' => 'Kembali ke proyek',
    'status_heading' => 'Status serah terima',
    'view_invoices' => 'Lihat tagihan',
    'files_heading' => 'Berkas serah terima',
    'files_empty' => 'Belum ada berkas',
    'files_empty_hint' => 'Tim kami akan mengunggah berkas serah terima di sini.',
    'file_label' => 'Berkas :type',
    'download' => 'Unduh',
    'method' => 'Metode serah terima',
    'expires' => 'berlaku sampai :date',
    'expired' => 'masa berlaku sudah berakhir',

    'states' => [
        'not_started' => [
            'title' => 'Belum dimulai',
            'body' => 'Serah terima tersedia setelah pekerjaan selesai. Anda akan diberi tahu saat tiba waktunya.',
        ],
        'no_final_invoice' => [
            'title' => 'Terkunci: invoice final belum terbit',
            'body' => 'Berkas serah terima terbuka setelah invoice final terbit dan dilunasi. Tim kami akan menerbitkannya saat pekerjaan selesai.',
        ],
        'final_unpaid' => [
            'title' => 'Terkunci: invoice final belum lunas',
            'body' => 'Berkas serah terima terbuka otomatis setelah invoice final dilunasi dan pembayarannya diverifikasi.',
        ],
        'ready' => [
            'title' => 'Siap diunduh',
            'body' => 'Invoice final sudah lunas. Berkas serah terima Anda tersedia di bawah.',
        ],
    ],
];
