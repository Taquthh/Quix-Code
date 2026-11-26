<?php

return [
    'greeting' => 'Halo :name,',
    'salutation' => 'Salam, Tim Quix Code',

    'quotation_sent' => [
        'subject' => 'Penawaran baru v:version untuk :project',
        'intro' => 'Kami telah mengirim penawaran versi :version untuk proyek ":project".',
        'total' => 'Total penawaran: :total',
        'valid_until' => 'Penawaran berlaku sampai :date.',
        'no_limit' => 'Penawaran ini tidak memiliki batas waktu.',
        'action' => 'Lihat penawaran',
        'outro' => 'Di portal Anda bisa menyetujui, menolak, atau bernegosiasi lewat chat.',
    ],

    'quotation_expiring' => [
        'subject' => 'Penawaran v:version untuk :project segera berakhir',
        'intro' => 'Penawaran versi :version untuk proyek ":project" berlaku sampai :date (:when).',
        'days' => '{0} berakhir hari ini|{1} tinggal 1 hari|[2,*] tinggal :count hari',
        'action' => 'Lihat penawaran',
        'outro' => 'Jika masih perlu waktu atau perubahan, tulis di chat sebelum penawaran berakhir.',
    ],

    'dp_invoice' => [
        'subject' => 'Invoice DP :number untuk :project',
        'intro' => 'Terima kasih telah menyetujui penawaran proyek ":project". Invoice DP sudah terbit.',
        'number' => 'Nomor invoice: :number',
        'amount' => 'Jumlah: :amount',
        'due' => 'Jatuh tempo: :date',
        'no_due' => 'Tanpa tanggal jatuh tempo.',
        'action' => 'Lihat invoice',
        'outro' => 'Pengerjaan dimulai setelah pembayaran DP kami verifikasi.',
    ],

    'chat_digest' => [
        'subject_admin' => 'Pesan baru dari klien di :project',
        'subject_client' => 'Pesan baru dari tim Quix Code di :project',
        'intro' => '{1} Ada 1 pesan baru yang belum dibaca di proyek ":project".|[2,*] Ada :count pesan baru yang belum dibaca di proyek ":project".',
        'hint' => 'Isi pesan tidak disertakan di email ini. Buka chat untuk membacanya.',
        'action' => 'Buka chat',
    ],

    'invoice_issued' => [
        'subject' => 'Invoice :number untuk :project',
        'intro' => 'Invoice baru untuk proyek ":project" sudah terbit.',
        'number' => 'Nomor invoice: :number',
        'amount' => 'Jumlah: :amount',
        'due' => 'Jatuh tempo: :date',
        'no_due' => 'Tanpa tanggal jatuh tempo.',
        'action' => 'Lihat tagihan',
        'outro' => 'Setelah membayar, unggah bukti pembayaran di halaman tagihan.',
    ],

    'payment_verified' => [
        'subject' => 'Pembayaran invoice :number diterima',
        'intro' => 'Bukti pembayaran Anda untuk invoice :number (proyek ":project") sudah kami verifikasi.',
        'amount' => 'Pembayaran diterima: :amount',
        'remaining' => 'Sisa tagihan: :amount',
        'paid_off' => 'Invoice ini sudah lunas.',
        'action' => 'Lihat tagihan',
        'outro' => 'Terima kasih atas pembayarannya.',
    ],

    'payment_rejected' => [
        'subject' => 'Bukti pembayaran invoice :number perlu diperbaiki',
        'intro' => 'Bukti pembayaran Anda untuk invoice :number (proyek ":project") belum dapat kami terima.',
        'amount' => 'Jumlah yang Anda kirim: :amount',
        'reason' => 'Alasan: :reason',
        'action' => 'Unggah bukti baru',
        'outro' => 'Anda dapat mengunggah ulang bukti pembayaran yang benar di halaman tagihan.',
    ],

    'ticket_replied' => [
        'subject' => 'Balasan baru untuk permintaan ":ticket"',
        'intro' => 'Tim kami membalas permintaan ":ticket" di proyek ":project".',
        'hint' => 'Isi balasan tidak disertakan di email ini. Buka portal untuk membacanya.',
        'action' => 'Buka portal',
    ],
];
