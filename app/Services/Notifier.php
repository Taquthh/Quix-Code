<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\RevisionTicket;
use App\Models\TicketComment;
use App\Models\User;
use App\Notifications\Contracts\UsesNotificationTemplate;
use App\Notifications\DownPaymentInvoiceNotification;
use App\Notifications\InvoiceIssuedNotification;
use App\Notifications\InvoiceReminderNotification;
use App\Notifications\PaymentRejectedNotification;
use App\Notifications\PaymentVerifiedNotification;
use App\Notifications\QuotationExpiringNotification;
use App\Notifications\QuotationSentNotification;
use App\Notifications\TicketRepliedNotification;
use Illuminate\Notifications\Notification;
use Throwable;

/**
 * Pengiriman email transaksional (T3-09, dilengkapi T4-09 dengan lonceng portal). Setiap pengiriman dicatat di notifications_log, dan kegagalan
 * email tidak pernah menggagalkan alur utama (kirim quotation, persetujuan, scheduler).
 */
class Notifier
{
    public function quotationSent(Quotation $quotation): void
    {
        $project = $quotation->project()->with('client')->first();

        if ($project !== null) {
            $this->deliver($project->client, new QuotationSentNotification($quotation, $project), 'quotation_sent');
        }
    }

    public function quotationExpiring(Quotation $quotation): void
    {
        $project = $quotation->project()->with('client')->first();

        if ($project !== null) {
            $this->deliver($project->client, new QuotationExpiringNotification($quotation, $project), 'quotation_expiring');
        }
    }

    public function downPaymentInvoice(Invoice $invoice): void
    {
        $project = $invoice->project()->with('client')->first();

        if ($project !== null) {
            $this->deliver($project->client, new DownPaymentInvoiceNotification($invoice, $project), 'dp_invoice');
        }
    }

    public function invoiceIssued(Invoice $invoice): void
    {
        $project = Project::query()->with('client')->find($invoice->project_id);

        if ($project !== null) {
            $this->deliver(User::query()->find($project->client_id), new InvoiceIssuedNotification($invoice, $project), 'invoice_issued');
        }
    }

    /** Pengingat invoice belum dibayar (T5-11). $kind: due_soon | due_today | overdue. */
    public function invoiceReminder(Invoice $invoice, string $kind): void
    {
        $project = Project::query()->with('client')->find($invoice->project_id);

        if ($project !== null) {
            $this->deliver(User::query()->find($project->client_id), new InvoiceReminderNotification($invoice, $project, $kind), 'invoice_reminder_'.$kind);
        }
    }

    public function paymentVerified(Payment $payment): void
    {
        $invoice = Invoice::query()->find($payment->invoice_id);
        $project = $invoice !== null ? Project::query()->with('client')->find($invoice->project_id) : null;

        if ($invoice !== null && $project !== null) {
            $this->deliver(User::query()->find($project->client_id), new PaymentVerifiedNotification($payment, $invoice, $project), 'payment_verified');
        }
    }

    public function paymentRejected(Payment $payment): void
    {
        $invoice = Invoice::query()->find($payment->invoice_id);
        $project = $invoice !== null ? Project::query()->with('client')->find($invoice->project_id) : null;

        if ($invoice !== null && $project !== null) {
            $this->deliver(User::query()->find($project->client_id), new PaymentRejectedNotification($payment, $invoice, $project), 'payment_rejected');
        }
    }

    /** Hanya balasan dari tim (admin atau staf) yang memberi tahu klien; komentar klien sendiri tidak. */
    public function ticketReplied(TicketComment $comment): void
    {
        $author = User::query()->find($comment->user_id);
        $ticket = RevisionTicket::query()->find($comment->ticket_id);
        $project = $ticket !== null ? Project::query()->with('client')->find($ticket->project_id) : null;

        if ($author === null || $ticket === null || $project === null) {
            return;
        }

        if (! ($author->isAdmin() || $author->isStaff()) || $author->is($project->client)) {
            return;
        }

        $this->deliver(User::query()->find($project->client_id), new TicketRepliedNotification($comment, $ticket, $project), 'ticket_replied');
    }

    /**
     * Kirim satu email dan catat hasilnya. Staf tidak pernah menerima email negosiasi atau tagihan.
     *
     * @return bool true bila terkirim
     */
    public function deliver(?User $user, Notification $notification, string $template): bool
    {
        if ($user === null || ! $user->is_active || $user->isStaff() || blank($user->email)) {
            return false;
        }

        $locale = $user->locale ?: config('app.locale');

        // WhatsApp lebih dulu (hanya klien beropt-in); email menjadi cadangan bila gagal (T5-10).
        $templated = $notification instanceof UsesNotificationTemplate ? $notification : null;
        $whatsapp = WhatsappSender::NOT_ATTEMPTED;

        if ($templated !== null) {
            $whatsapp = app(WhatsappSender::class)->send($user, $templated, $locale);

            if ($whatsapp === WhatsappSender::DUPLICATE) {
                return true;
            }

            if ($whatsapp === WhatsappSender::SENT) {
                return $this->notifyBellOnly($user, $templated, $locale);
            }
        }

        $templateVersion = $this->applyTemplate($user, $notification, $locale);

        $log = NotificationLog::create([
            'user_id' => $user->id,
            'channel' => 'email',
            'template' => $template,
            'template_version' => $templateVersion,
            'locale' => $locale,
            'fallback_used' => $whatsapp === WhatsappSender::FAILED,
            'status' => 'queued',
        ]);

        try {
            $user->notify($notification->locale($locale));

            $log->forceFill(['status' => 'sent', 'sent_at' => now()])->save();

            return true;
        } catch (Throwable $e) {
            report($e);

            $log->forceFill(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 1000)])->save();

            return false;
        }
    }

    /** WhatsApp sudah terkirim: hanya lonceng portal yang disimpan, email tidak dikirim. */
    private function notifyBellOnly(User $user, UsesNotificationTemplate&Notification $notification, string $locale): bool
    {
        try {
            $notification->suppressMail();
            $user->notify($notification->locale($locale));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * Pasang template buatan admin bila ada untuk kunci dan bahasa penerima (T5-13).
     * Tanpa template, atau bila template gagal dirender, email memakai teks bawaan lang/.
     *
     * @return int|null versi template yang dipakai, null bila teks bawaan
     */
    private function applyTemplate(User $user, Notification $notification, string $locale): ?int
    {
        if (! $notification instanceof UsesNotificationTemplate) {
            return null;
        }

        try {
            $service = app(NotificationTemplateService::class);
            $template = $service->latest($notification->templateKey(), $locale, 'email');

            if ($template === null) {
                return null;
            }

            $variables = $service->variablesFor($notification, $user, $locale);
            $notification->applyTemplate($service->renderTemplate($template, $variables));

            return $template->version;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
