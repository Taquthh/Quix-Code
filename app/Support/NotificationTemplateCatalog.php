<?php

namespace App\Support;

/**
 * Katalog template notifikasi (T5-13): kunci yang boleh diedit admin, variabel yang sah per kunci,
 * isi bawaan ID/EN, dan contoh nilai untuk pratinjau. Kunci sama dengan kolom notifications_log.template.
 * Variabel ditulis {{nama}}; semua variabel di luar daftar ditolak saat menyimpan.
 */
class NotificationTemplateCatalog
{
    public const LOCALES = ['id', 'en'];

    public const CHANNELS = ['email', 'whatsapp'];

    /** Variabel yang tersedia di semua template. */
    private const COMMON = ['name', 'project', 'url'];

    /**
     * Variabel khusus per kunci (di luar COMMON).
     *
     * @return array<string, list<string>>
     */
    private static function extraVariables(): array
    {
        return [
            'quotation_sent' => ['version', 'total', 'validity'],
            'quotation_expiring' => ['version', 'date', 'when'],
            'dp_invoice' => ['number', 'amount', 'due_line'],
            'invoice_issued' => ['number', 'amount', 'due_line'],
            'invoice_reminder_due_soon' => ['number', 'amount', 'due_line', 'days'],
            'invoice_reminder_due_today' => ['number', 'amount', 'due_line'],
            'invoice_reminder_overdue' => ['number', 'amount', 'due_line'],
            'payment_verified' => ['number', 'amount', 'balance_line'],
            'payment_rejected' => ['number', 'amount', 'reason'],
            'ticket_replied' => ['ticket'],
        ];
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::extraVariables());
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::extraVariables());
    }

    /** @return list<string> */
    public static function variables(string $key): array
    {
        return array_values(array_unique([...self::COMMON, ...(self::extraVariables()[$key] ?? [])]));
    }

    /**
     * Isi bawaan. Email: paragraf dipisah baris kosong, tampil sebelum tombol aksi; sapaan dan salam penutup
     * tetap dari lang/*\/mail.php. WhatsApp: satu pesan lengkap dengan sapaan, tanpa subjek.
     *
     * @return array{subject: ?string, body: string}
     */
    public static function defaults(string $key, string $locale, string $channel): array
    {
        $text = self::texts()[$key][$locale] ?? self::texts()[$key]['id'] ?? null;

        if ($text === null || ! in_array($channel, self::CHANNELS, true)) {
            return ['subject' => $channel === 'email' ? '' : null, 'body' => ''];
        }

        return [
            'subject' => $channel === 'email' ? $text['subject'] : null,
            'body' => $text[$channel],
        ];
    }

    /**
     * Contoh nilai untuk pratinjau di editor.
     *
     * @return array<string, string>
     */
    public static function sample(string $locale): array
    {
        $en = $locale === 'en';

        return [
            'name' => 'Budi',
            'project' => $en ? 'Coffee Shop Website' : 'Website Toko Kopi',
            'url' => 'https://quix.example/portal/projects/01SAMPLE',
            'version' => '2',
            'total' => $en ? 'IDR 20,000,000' : 'Rp 20.000.000',
            'validity' => $en ? 'The quotation is valid until 15 December 2026.' : 'Penawaran berlaku sampai 15 Desember 2026.',
            'date' => $en ? '15 December 2026' : '15 Desember 2026',
            'when' => $en ? '3 days left' : 'tinggal 3 hari',
            'number' => 'INV-2026-0012',
            'amount' => $en ? 'IDR 6,000,000' : 'Rp 6.000.000',
            'due_line' => $en ? 'Due date: 20 December 2026' : 'Jatuh tempo: 20 Desember 2026',
            'balance_line' => $en ? 'Remaining balance: IDR 14,000,000' : 'Sisa tagihan: Rp 14.000.000',
            'reason' => $en ? 'The transfer amount does not match the invoice.' : 'Nominal transfer tidak sesuai dengan invoice.',
            'ticket' => $en ? 'Change the header colour' : 'Ubah warna header',
            'days' => '3',
        ];
    }

    /**
     * @return array<string, array<string, array{subject: string, email: string, whatsapp: string}>>
     */
    private static function texts(): array
    {
        return [
            'quotation_sent' => [
                'id' => [
                    'subject' => 'Penawaran baru v{{version}} untuk {{project}}',
                    'email' => "Kami telah mengirim penawaran versi {{version}} untuk proyek \"{{project}}\".\n\nTotal penawaran: {{total}}\n\n{{validity}}\n\nDi portal Anda bisa menyetujui, menolak, atau bernegosiasi lewat chat.",
                    'whatsapp' => "Halo {{name}}, penawaran v{{version}} untuk proyek \"{{project}}\" sudah kami kirim.\nTotal: {{total}}\n{{validity}}\nLihat dan tanggapi di: {{url}}",
                ],
                'en' => [
                    'subject' => 'New quotation v{{version}} for {{project}}',
                    'email' => "We have sent quotation version {{version}} for the project \"{{project}}\".\n\nQuotation total: {{total}}\n\n{{validity}}\n\nIn the portal you can accept, decline, or negotiate through chat.",
                    'whatsapp' => "Hello {{name}}, quotation v{{version}} for the project \"{{project}}\" has been sent.\nTotal: {{total}}\n{{validity}}\nView and respond here: {{url}}",
                ],
            ],
            'quotation_expiring' => [
                'id' => [
                    'subject' => 'Penawaran v{{version}} untuk {{project}} segera berakhir',
                    'email' => "Penawaran versi {{version}} untuk proyek \"{{project}}\" berlaku sampai {{date}} ({{when}}).\n\nJika masih perlu waktu atau perubahan, tulis di chat sebelum penawaran berakhir.",
                    'whatsapp' => 'Halo {{name}}, penawaran v{{version}} untuk "{{project}}" berlaku sampai {{date}} ({{when}}). Butuh waktu atau perubahan? Tulis di chat: {{url}}',
                ],
                'en' => [
                    'subject' => 'Quotation v{{version}} for {{project}} is about to expire',
                    'email' => "Quotation version {{version}} for the project \"{{project}}\" is valid until {{date}} ({{when}}).\n\nIf you need more time or changes, write to us in chat before it expires.",
                    'whatsapp' => 'Hello {{name}}, quotation v{{version}} for "{{project}}" is valid until {{date}} ({{when}}). Need more time or changes? Write to us in chat: {{url}}',
                ],
            ],
            'dp_invoice' => [
                'id' => [
                    'subject' => 'Invoice DP {{number}} untuk {{project}}',
                    'email' => "Terima kasih telah menyetujui penawaran proyek \"{{project}}\". Invoice DP sudah terbit.\n\nNomor invoice: {{number}}\n\nJumlah: {{amount}}\n\n{{due_line}}\n\nPengerjaan dimulai setelah pembayaran DP kami verifikasi.",
                    'whatsapp' => "Halo {{name}}, invoice DP {{number}} untuk \"{{project}}\" sudah terbit.\nJumlah: {{amount}}\n{{due_line}}\nLihat invoice: {{url}}",
                ],
                'en' => [
                    'subject' => 'Down payment invoice {{number}} for {{project}}',
                    'email' => "Thank you for accepting the quotation for \"{{project}}\". Your down payment invoice has been issued.\n\nInvoice number: {{number}}\n\nAmount: {{amount}}\n\n{{due_line}}\n\nWork starts once we have verified your down payment.",
                    'whatsapp' => "Hello {{name}}, down payment invoice {{number}} for \"{{project}}\" has been issued.\nAmount: {{amount}}\n{{due_line}}\nView invoice: {{url}}",
                ],
            ],
            'invoice_issued' => [
                'id' => [
                    'subject' => 'Invoice {{number}} untuk {{project}}',
                    'email' => "Invoice baru untuk proyek \"{{project}}\" sudah terbit.\n\nNomor invoice: {{number}}\n\nJumlah: {{amount}}\n\n{{due_line}}\n\nSetelah membayar, unggah bukti pembayaran di halaman tagihan.",
                    'whatsapp' => "Halo {{name}}, invoice {{number}} untuk \"{{project}}\" sudah terbit.\nJumlah: {{amount}}\n{{due_line}}\nLihat tagihan: {{url}}",
                ],
                'en' => [
                    'subject' => 'Invoice {{number}} for {{project}}',
                    'email' => "A new invoice for project \"{{project}}\" has been issued.\n\nInvoice number: {{number}}\n\nAmount: {{amount}}\n\n{{due_line}}\n\nAfter paying, upload your proof of payment on the invoices page.",
                    'whatsapp' => "Hello {{name}}, invoice {{number}} for \"{{project}}\" has been issued.\nAmount: {{amount}}\n{{due_line}}\nView invoices: {{url}}",
                ],
            ],
            'invoice_reminder_due_soon' => [
                'id' => [
                    'subject' => 'Invoice {{number}} untuk {{project}} segera jatuh tempo',
                    'email' => "Invoice untuk proyek \"{{project}}\" akan jatuh tempo dalam {{days}} hari.\n\nNomor invoice: {{number}}\n\nSisa tagihan: {{amount}}\n\n{{due_line}}\n\nJika sudah membayar, unggah bukti pembayaran di halaman tagihan. Abaikan email ini jika bukti sudah Anda kirim.",
                    'whatsapp' => 'Halo {{name}}, invoice {{number}} untuk "{{project}}" jatuh tempo dalam {{days}} hari. Sisa tagihan: {{amount}}. {{due_line}} Lihat tagihan: {{url}}',
                ],
                'en' => [
                    'subject' => 'Invoice {{number}} for {{project}} is due soon',
                    'email' => "The invoice for project \"{{project}}\" is due in {{days}} days.\n\nInvoice number: {{number}}\n\nBalance due: {{amount}}\n\n{{due_line}}\n\nIf you have already paid, upload the proof of payment on the invoice page. Ignore this email if you already sent it.",
                    'whatsapp' => 'Hello {{name}}, invoice {{number}} for "{{project}}" is due in {{days}} days. Balance due: {{amount}}. {{due_line}} View invoices: {{url}}',
                ],
            ],
            'invoice_reminder_due_today' => [
                'id' => [
                    'subject' => 'Invoice {{number}} untuk {{project}} jatuh tempo hari ini',
                    'email' => "Invoice untuk proyek \"{{project}}\" jatuh tempo hari ini.\n\nNomor invoice: {{number}}\n\nSisa tagihan: {{amount}}\n\n{{due_line}}\n\nJika sudah membayar, unggah bukti pembayaran di halaman tagihan. Abaikan email ini jika bukti sudah Anda kirim.",
                    'whatsapp' => 'Halo {{name}}, invoice {{number}} untuk "{{project}}" jatuh tempo hari ini. Sisa tagihan: {{amount}}. Lihat tagihan: {{url}}',
                ],
                'en' => [
                    'subject' => 'Invoice {{number}} for {{project}} is due today',
                    'email' => "The invoice for project \"{{project}}\" is due today.\n\nInvoice number: {{number}}\n\nBalance due: {{amount}}\n\n{{due_line}}\n\nIf you have already paid, upload the proof of payment on the invoice page. Ignore this email if you already sent it.",
                    'whatsapp' => 'Hello {{name}}, invoice {{number}} for "{{project}}" is due today. Balance due: {{amount}}. View invoices: {{url}}',
                ],
            ],
            'invoice_reminder_overdue' => [
                'id' => [
                    'subject' => 'Invoice {{number}} untuk {{project}} sudah lewat jatuh tempo',
                    'email' => "Invoice untuk proyek \"{{project}}\" sudah lewat jatuh tempo.\n\nNomor invoice: {{number}}\n\nSisa tagihan: {{amount}}\n\n{{due_line}}\n\nJika sudah membayar, unggah bukti pembayaran di halaman tagihan. Abaikan email ini jika bukti sudah Anda kirim.",
                    'whatsapp' => 'Halo {{name}}, invoice {{number}} untuk "{{project}}" sudah lewat jatuh tempo. Sisa tagihan: {{amount}}. {{due_line}} Lihat tagihan: {{url}}',
                ],
                'en' => [
                    'subject' => 'Invoice {{number}} for {{project}} is past due',
                    'email' => "The invoice for project \"{{project}}\" is past its due date.\n\nInvoice number: {{number}}\n\nBalance due: {{amount}}\n\n{{due_line}}\n\nIf you have already paid, upload the proof of payment on the invoice page. Ignore this email if you already sent it.",
                    'whatsapp' => 'Hello {{name}}, invoice {{number}} for "{{project}}" is past its due date. Balance due: {{amount}}. {{due_line}} View invoices: {{url}}',
                ],
            ],
            'payment_verified' => [
                'id' => [
                    'subject' => 'Pembayaran invoice {{number}} diterima',
                    'email' => "Bukti pembayaran Anda untuk invoice {{number}} (proyek \"{{project}}\") sudah kami verifikasi.\n\nPembayaran diterima: {{amount}}\n\n{{balance_line}}\n\nTerima kasih atas pembayarannya.",
                    'whatsapp' => 'Halo {{name}}, pembayaran {{amount}} untuk invoice {{number}} ("{{project}}") sudah kami verifikasi. {{balance_line}} Terima kasih! {{url}}',
                ],
                'en' => [
                    'subject' => 'Payment for invoice {{number}} received',
                    'email' => "We have verified your proof of payment for invoice {{number}} (project \"{{project}}\").\n\nPayment received: {{amount}}\n\n{{balance_line}}\n\nThank you for your payment.",
                    'whatsapp' => 'Hello {{name}}, your payment of {{amount}} for invoice {{number}} ("{{project}}") has been verified. {{balance_line}} Thank you! {{url}}',
                ],
            ],
            'payment_rejected' => [
                'id' => [
                    'subject' => 'Bukti pembayaran invoice {{number}} perlu diperbaiki',
                    'email' => "Bukti pembayaran Anda untuk invoice {{number}} (proyek \"{{project}}\") belum dapat kami terima.\n\nJumlah yang Anda kirim: {{amount}}\n\nAlasan: {{reason}}\n\nAnda dapat mengunggah ulang bukti pembayaran yang benar di halaman tagihan.",
                    'whatsapp' => 'Halo {{name}}, bukti pembayaran untuk invoice {{number}} ("{{project}}") belum dapat kami terima. Alasan: {{reason}} Unggah ulang di: {{url}}',
                ],
                'en' => [
                    'subject' => 'Proof of payment for invoice {{number}} needs attention',
                    'email' => "We could not accept your proof of payment for invoice {{number}} (project \"{{project}}\").\n\nAmount you submitted: {{amount}}\n\nReason: {{reason}}\n\nYou can upload the correct proof of payment again on the invoices page.",
                    'whatsapp' => 'Hello {{name}}, we could not accept your proof of payment for invoice {{number}} ("{{project}}"). Reason: {{reason}} Upload it again here: {{url}}',
                ],
            ],
            'ticket_replied' => [
                'id' => [
                    'subject' => 'Balasan baru untuk permintaan "{{ticket}}"',
                    'email' => "Tim kami membalas permintaan \"{{ticket}}\" di proyek \"{{project}}\".\n\nIsi balasan tidak disertakan di email ini. Buka portal untuk membacanya.",
                    'whatsapp' => 'Halo {{name}}, tim kami membalas permintaan "{{ticket}}" di proyek "{{project}}". Baca balasannya di portal: {{url}}',
                ],
                'en' => [
                    'subject' => 'New reply to your request "{{ticket}}"',
                    'email' => "Our team replied to your request \"{{ticket}}\" in project \"{{project}}\".\n\nThe reply text is not included in this email. Open the portal to read it.",
                    'whatsapp' => 'Hello {{name}}, our team replied to your request "{{ticket}}" in project "{{project}}". Read the reply in the portal: {{url}}',
                ],
            ],
        ];
    }
}
