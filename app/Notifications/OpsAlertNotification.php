<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email ke admin: job gagal permanen atau antrean menumpuk (T5-09). Hanya email; tidak masuk lonceng. */
class OpsAlertNotification extends Notification
{
    /** @param array<string, mixed> $context */
    public function __construct(public readonly string $kind, public readonly array $context) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__("mail.ops_alert.subject_{$this->kind}"))
            ->greeting(__('mail.greeting', ['name' => $notifiable->name]));

        if ($this->kind === 'failed_job') {
            $mail->line(__('mail.ops_alert.failed_job_intro', ['job' => $this->context['job'] ?? '-', 'queue' => $this->context['queue'] ?? '-']))
                ->line(__('mail.ops_alert.error', ['error' => $this->context['error'] ?? '-']));
        } else {
            $mail->line(__('mail.ops_alert.queue_busy_intro', [
                'queue' => $this->context['queue'] ?? '-',
                'connection' => $this->context['connection'] ?? '-',
                'size' => $this->context['size'] ?? 0,
            ]));
        }

        return $mail->line(__('mail.ops_alert.outro'))->salutation(__('mail.salutation'));
    }
}
