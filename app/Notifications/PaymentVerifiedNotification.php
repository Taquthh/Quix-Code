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

/** Email dan lonceng ke klien: bukti bayar diterima admin. */
class PaymentVerifiedNotification extends Notification implements UsesNotificationTemplate
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
        $currency = $this->invoice->currency;

        $mail = (new MailMessage)
            ->subject(__('mail.payment_verified.subject', ['number' => $this->invoice->invoice_number, 'project' => $this->project->title]))
            ->greeting(__('mail.greeting', ['name' => $notifiable->name]))
            ->line(__('mail.payment_verified.intro', ['number' => $this->invoice->invoice_number, 'project' => $this->project->title]))
            ->line(__('mail.payment_verified.amount', ['amount' => Money::of((int) $this->payment->amount, $currency)->format()]))
            ->line($this->balanceLine())
            ->action(__('mail.payment_verified.action'), route('portal.projects.invoices', $this->project->id))
            ->line(__('mail.payment_verified.outro'))
            ->salutation(__('mail.salutation'));

        return $this->withTemplate($mail);
    }

    public function templateKey(): string
    {
        return 'payment_verified';
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
            'balance_line' => $this->balanceLine(),
        ];
    }

    private function balanceLine(): string
    {
        $currency = $this->invoice->currency;
        $remaining = Money::of((int) $this->invoice->amount, $currency)
            ->subtract(Money::of((int) $this->invoice->paid_amount, $currency));

        return $remaining->isZero() || $remaining->isNegative()
            ? __('mail.payment_verified.paid_off')
            : __('mail.payment_verified.remaining', ['amount' => $remaining->format()]);
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
