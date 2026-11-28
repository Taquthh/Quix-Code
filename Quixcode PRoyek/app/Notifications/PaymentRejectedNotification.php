<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Notifications\Concerns\AppliesTemplate;
use App\Notifications\Contracts\UsesNotificationTemplate;
use App\Support\InAppNotification;
use App\Support\Money;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email dan lonceng ke klien: bukti bayar ditolak beserta alasannya, dan klien boleh mengunggah ulang. */
class PaymentRejectedNotification extends Notification implements UsesNotificationTemplate
{
    use AppliesTemplate;

    public function __construct(
        public readonly Payment $payment,
        public readonly Invoice $invoice,
        public readonly Project $project,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $this->deliveryChannels();
    }

    /** @param  User  $notifiable */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__('mail.payment_rejected.subject', ['number' => $this->invoice->invoice_number, 'project' => $this->project->title]))
            ->greeting(__('mail.greeting', ['name' => $notifiable->name]))
            ->line(__('mail.payment_rejected.intro', ['number' => $this->invoice->invoice_number, 'project' => $this->project->title]))
            ->line(__('mail.payment_rejected.amount', ['amount' => Money::of((int) $this->payment->amount, $this->invoice->currency)->format()]))
            ->line(__('mail.payment_rejected.reason', ['reason' => (string) $this->payment->rejection_reason]))
            ->action(__('mail.payment_rejected.action'), route('portal.projects.invoices', $this->project->id))
            ->line(__('mail.payment_rejected.outro'))
            ->salutation(__('mail.salutation'));

        return $this->withTemplate($mail);
    }

    public function templateKey(): string
    {
        return 'payment_rejected';
    }

    /** @return array<string, string> */
    public function templateVariables(User $notifiable): array
    {
        return [
            'name' => $notifiable->name,
            'project' => $this->project->title,
            'url' => route('portal.projects.invoices', $this->project->id),
            'number' => (string) $this->invoice->invoice_number,
            'amount' => Money::of((int) $this->payment->amount, $this->invoice->currency)->format(),
            'reason' => (string) $this->payment->rejection_reason,
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return InAppNotification::payload('payment_rejected', $this->project, [
            'number' => $this->invoice->invoice_number,
            'amount' => (int) $this->payment->amount,
            'currency' => $this->invoice->money()->currency->value,
            'reason' => mb_substr((string) $this->payment->rejection_reason, 0, 300),
        ]);
    }
}
