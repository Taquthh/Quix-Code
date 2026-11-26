<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Support\InAppNotification;
use App\Support\Money;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email dan lonceng ke klien: bukti bayar diterima admin. */
class PaymentVerifiedNotification extends Notification
{
    public function __construct(
        public readonly Payment $payment,
        public readonly Invoice $invoice,
        public readonly Project $project,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /** @param  User  $notifiable */
    public function toMail(object $notifiable): MailMessage
    {
        $currency = $this->invoice->currency;
        $remaining = Money::of((int) $this->invoice->amount, $currency)
            ->subtract(Money::of((int) $this->invoice->paid_amount, $currency));

        return (new MailMessage)
            ->subject(__('mail.payment_verified.subject', ['number' => $this->invoice->invoice_number, 'project' => $this->project->title]))
            ->greeting(__('mail.greeting', ['name' => $notifiable->name]))
            ->line(__('mail.payment_verified.intro', ['number' => $this->invoice->invoice_number, 'project' => $this->project->title]))
            ->line(__('mail.payment_verified.amount', ['amount' => Money::of((int) $this->payment->amount, $currency)->format()]))
            ->line($remaining->isZero() || $remaining->isNegative()
                ? __('mail.payment_verified.paid_off')
                : __('mail.payment_verified.remaining', ['amount' => $remaining->format()]))
            ->action(__('mail.payment_verified.action'), route('portal.projects.invoices', $this->project->id))
            ->line(__('mail.payment_verified.outro'))
            ->salutation(__('mail.salutation'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return InAppNotification::payload('payment_verified', $this->project, [
            'number' => $this->invoice->invoice_number,
            'amount' => (int) $this->payment->amount,
            'currency' => $this->invoice->money()->currency->value,
        ]);
    }
}
