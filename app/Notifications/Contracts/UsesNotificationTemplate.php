<?php

namespace App\Notifications\Contracts;

use App\Models\User;
use App\Support\RenderedTemplate;

/**
 * Notifikasi email yang isinya bisa diganti template buatan admin (T5-13). Notifier mengisi variabel
 * lewat templateVariables() lalu memasang hasilnya sebelum email dikirim.
 */
interface UsesNotificationTemplate
{
    /** Kunci di NotificationTemplateCatalog, sama dengan nama template di notifications_log. */
    public function templateKey(): string;

    /**
     * Nilai variabel untuk penerima ini, dihitung dalam bahasa penerima.
     *
     * @return array<string, string>
     */
    public function templateVariables(User $notifiable): array;

    public function applyTemplate(RenderedTemplate $template): static;

    /** Kunci kejadian yang unik, mis. invoice_issued:<id>; dipakai agar WhatsApp tidak terkirim ganda (T5-10). */
    public function deliveryReference(): string;

    /** Dipanggil bila WhatsApp sudah terkirim: email tidak dikirim, lonceng portal tetap. */
    public function suppressMail(): static;
}
