<?php

return [
    'title' => 'Bantuan',
    'subtitle' => 'Jawaban singkat dan cara menghubungi tim tanpa meninggalkan portal.',
    'back' => 'Kembali ke proyek',
    'link' => 'Bantuan',
    'onboarding' => [
        'heading' => 'Langkah awal Anda',
        'intro' => 'Tiga langkah dari quotation sampai proyek berjalan.',
        'state' => [
            'done' => 'selesai',
            'current' => 'langkah saat ini',
            'upcoming' => 'berikutnya',
        ],
        'steps' => [
            'approve_quotation' => [
                'title' => 'Setujui quotation',
                'description' => 'Tinjau rincian dan harga, diskusikan lewat chat bila perlu, lalu setujui.',
                'action' => 'Buka quotation',
            ],
            'pay_dp' => [
                'title' => 'Bayar DP',
                'description' => 'Invoice DP terbit otomatis setelah persetujuan. Bayar, lalu unggah bukti pembayaran.',
                'action' => 'Buka tagihan',
            ],
            'track_progress' => [
                'title' => 'Pantau progres',
                'description' => 'Lihat tahapan, progres, dan perkembangan pekerjaan kapan saja di ringkasan proyek.',
                'action' => 'Buka ringkasan proyek',
            ],
        ],
    ],
    'faq_heading' => 'Pertanyaan umum',
    'faq' => [
        [
            'question' => 'Bagaimana cara menyetujui quotation?',
            'answer' => 'Buka menu Quotation pada proyek Anda, tinjau rinciannya, lalu pilih setujui dan konfirmasi pernyataan persetujuan. Bila ada yang perlu diubah, tulis di chat pada halaman yang sama.',
        ],
        [
            'question' => 'Bagaimana cara membayar DP?',
            'answer' => 'Setelah quotation disetujui, Invoice DP terbit di menu Tagihan. Lakukan pembayaran sesuai petunjuk di invoice, lalu unggah bukti bayar di halaman yang sama.',
        ],
        [
            'question' => 'Bagaimana saya tahu bukti bayar sudah diterima?',
            'answer' => 'Tim memverifikasi bukti bayar Anda. Hasilnya muncul di lonceng notifikasi dan dikirim lewat email. Bila ditolak, alasannya ditampilkan dan Anda bisa mengunggah ulang.',
        ],
        [
            'question' => 'Di mana saya memantau progres proyek?',
            'answer' => 'Di ringkasan proyek: tahapan, persentase progres, dan timeline pekerjaan. Setelah tahapan selesai, Anda bisa menerima hasilnya langsung dari halaman itu.',
        ],
        [
            'question' => 'Bagaimana mengajukan revisi atau melaporkan bug?',
            'answer' => 'Gunakan formulir permintaan di ringkasan proyek dan lampirkan tangkapan layar bila ada. Setiap tahapan mendapat :rounds putaran revisi gratis; revisi di luar kuota ditandai berbayar (Change Request). Laporan bug tidak memakai kuota.',
        ],
        [
            'question' => 'Bagaimana mengubah bahasa, mata uang tampilan, atau notifikasi WhatsApp?',
            'answer' => 'Buka pengaturan akun Anda. Mata uang tampilan hanya perkiraan; angka resmi quotation dan invoice tetap memakai mata uang proyek. WhatsApp hanya dikirim bila Anda menyetujuinya.',
        ],
    ],
    'support_heading' => 'Hubungi dukungan',
    'support_intro' => 'Cara tercepat adalah chat pada proyek Anda. Tim akan membalas di sana dan Anda mendapat notifikasi.',
    'chat_project' => 'Chat dengan tim: :project',
    'no_active_project' => 'Belum ada proyek aktif. Proyek Anda akan muncul di sini, lengkap dengan chat ke tim.',
    'other_channels' => 'Kanal lain',
];
