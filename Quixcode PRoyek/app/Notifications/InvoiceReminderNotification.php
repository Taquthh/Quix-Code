<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use App\Notifications\Concerns\AppliesTemplate;
use App\Notifications\Contracts\UsesNotificationTemplate;
use App\Support\InAppNotification;
use App\Support\Money;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email dan lonceng ke klien: pengingat invoice belum dibayar (T5-11).
 * $kind: due_soon (H-N), due_today (H0), overdue (H+N).
 */
class InvoiceReminderNotification extends Notification implements UsesNotificationTemplate
{
    use AppliesTemplate;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly Project $project,
        public readonly string $kind,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $this->deliveryChannels();
    }

    /** @param  User  $notifiable */
    public function toMail(object $notifiable): MailMessage
    {
        $vars = ['number' => $this->invoice->invoice_number, 'project' => $this->project->title];

        $intro = __("mail.invoice_reminder.intro_{$this->kind}", [
            'project' => $this->project->title,
            'days' => (int) config('quix.automation.invoice_reminder_days'),
        ]);

        $mail = (new MailMessage)
            ->subject(__("mail.invoice_reminder.subject_{$this->kind}", $vars))
            ->greeting(__('mail.greeting', ['name' => $notifiable->name]))
            ->line($intro)
            ->line(__('mail.invoice_reminder.number', ['number' => $this->invoice->invoice_number]))
            ->line(__('mail.invoice_reminder.amount', ['amount' => $this->balance()->format()]));

        if ($this->invoice->dueDateLabel() !== null) {
            $mail->line(__('mail.invoice_reminder.due', ['date' => $this->invoice->dueDateLabel()]));
        }

        $mail
            ->action(__('mail.invoice_reminder.action'), route('portal.projects.invoices', $this->project->id))
            ->line(__('mail.invoice_reminder.outro'))
            ->salutation(__('mail.salutation'));

        return $this->withTemplate($mail);
    }

    public function templateKey(): string
    {
        return 'invoice_reminder_'.$this->kind;
    }

    /** @return array<string, string> */
    public function templateVariables(User $notifiable): array
    {
        $variables = [
            'name' => $notifiable->name,
            'project' => $this->project->title,
            'number' => $this->invoice->invoice_number,
            'amount' => $this->balance()->format(),
            'due_line' => $this->invoice->dueDateLabel() !== null
                ? __('mail.invoice_reminder.due', ['date' => $this->invoice->dueDateLabel()])
                : '',
            'url' => route('portal.projects.invoices', $this->project->id),
        ];

        if ($this->kind === 'due_soon') {
            $variables['days'] = (string) (int) config('quix.automation.invoice_reminder_days');
        }

        return $variables;
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $balance = $this->balance();

        return InAppNotification::payload("invoice_{$this->kind}", $this->project, [
            'number' => $this->invoice->invoice_number,
            'amount' => $balance->amount,
            'currency' => $balance->currency->value,
        ]);
    }

    /** Sisa tagihan (pembayaran sebagian sudah dikurangi). */
    private function balance(): Money
    {
        return Money::of(max(0, (int) $this->invoice->amount - (int) $this->invoice->paid_amount), $this->invoice->currency);
    }
}
