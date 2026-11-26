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
use App\Notifications\DownPaymentInvoiceNotification;
use App\Notifications\InvoiceIssuedNotification;
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

        $log = NotificationLog::create([
            'user_id' => $user->id,
            'channel' => 'email',
            'template' => $template,
            'status' => 'queued',
        ]);

        try {
            $user->notify($notification->locale($user->locale ?: config('app.locale')));

            $log->forceFill(['status' => 'sent', 'sent_at' => now()])->save();

            return true;
        } catch (Throwable $e) {
            report($e);

            $log->forceFill(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 1000)])->save();

            return false;
        }
    }
}
