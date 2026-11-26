<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use App\Support\InAppNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email ke klien: Invoice DP terbit setelah penawaran disetujui. */
class DownPaymentInvoiceNotification extends Notification
{
    public function __construct(public readonly Invoice $invoice, public readonly Project $project) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /** @param  User  $notifiable */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('mail.dp_invoice.subject', ['number' => $this->invoice->invoice_number, 'project' => $this->project->title]))
            ->greeting(__('mail.greeting', ['name' => $notifiable->name]))
            ->line(__('mail.dp_invoice.intro', ['project' => $this->project->title]))
            ->line(__('mail.dp_invoice.number', ['number' => $this->invoice->invoice_number]))
            ->line(__('mail.dp_invoice.amount', ['amount' => $this->invoice->money()->format()]))
            ->line($this->invoice->dueDateLabel() !== null
                ? __('mail.dp_invoice.due', ['date' => $this->invoice->dueDateLabel()])
                : __('mail.dp_invoice.no_due'))
            ->action(__('mail.dp_invoice.action'), route('portal.projects.negotiation', $this->project->id))
            ->line(__('mail.dp_invoice.outro'))
            ->salutation(__('mail.salutation'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return InAppNotification::payload('dp_invoice', $this->project, [
            'number' => $this->invoice->invoice_number,
            'amount' => (int) $this->invoice->amount,
            'currency' => $this->invoice->money()->currency->value,
        ]);
    }
}
