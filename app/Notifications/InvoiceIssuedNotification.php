<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use App\Notifications\Concerns\AppliesTemplate;
use App\Notifications\Contracts\UsesNotificationTemplate;
use App\Support\InAppNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email dan lonceng ke klien: invoice baru diterbitkan admin. */
class InvoiceIssuedNotification extends Notification implements UsesNotificationTemplate
{
    use AppliesTemplate;

    public function __construct(public readonly Invoice $invoice, public readonly Project $project) {}

    /** @return list<string> Lonceng lebih dulu agar tetap tersimpan walau email gagal. */
    public function via(object $notifiable): array
    {
        return $this->deliveryChannels();
    }

    /** @param  User  $notifiable */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__('mail.invoice_issued.subject', ['number' => $this->invoice->invoice_number, 'project' => $this->project->title]))
            ->greeting(__('mail.greeting', ['name' => $notifiable->name]))
            ->line(__('mail.invoice_issued.intro', ['project' => $this->project->title]))
            ->line(__('mail.invoice_issued.number', ['number' => $this->invoice->invoice_number]))
            ->line(__('mail.invoice_issued.amount', ['amount' => $this->invoice->money()->format()]))
            ->line($this->invoice->dueDateLabel() !== null
                ? __('mail.invoice_issued.due', ['date' => $this->invoice->dueDateLabel()])
                : __('mail.invoice_issued.no_due'))
            ->action(__('mail.invoice_issued.action'), route('portal.projects.invoices', $this->project->id))
            ->line(__('mail.invoice_issued.outro'))
            ->salutation(__('mail.salutation'));

        return $this->withTemplate($mail);
    }

    public function templateKey(): string
    {
        return 'invoice_issued';
    }

    /** @return array<string, string> */
    public function templateVariables(User $notifiable): array
    {
        return [
            'name' => $notifiable->name,
            'project' => $this->project->title,
            'url' => route('portal.projects.invoices', $this->project->id),
            'number' => (string) $this->invoice->invoice_number,
            'amount' => $this->invoice->money()->format(),
            'due_line' => $this->invoice->dueDateLabel() !== null
                ? __('mail.invoice_issued.due', ['date' => $this->invoice->dueDateLabel()])
                : __('mail.invoice_issued.no_due'),
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return InAppNotification::payload('invoice_issued', $this->project, [
            'number' => $this->invoice->invoice_number,
            'amount' => (int) $this->invoice->amount,
            'currency' => $this->invoice->money()->currency->value,
        ]);
    }
}
