<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email ke admin: tiket belum dibalas tim melewati batas waktu (T5-11). Satu ringkasan per proses,
 * hanya email (tidak masuk lonceng, karena lonceng khusus klien).
 */
class TicketSlaNotification extends Notification
{
    private const MAX_LISTED = 10;

    /** @param list<array{title: string, project: string}> $tickets */
    public function __construct(public readonly array $tickets, public readonly int $hours) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $count = count($this->tickets);
        $vars = ['count' => $count, 'hours' => $this->hours];

        $mail = (new MailMessage)
            ->subject(__('mail.ticket_sla.subject', $vars))
            ->greeting(__('mail.greeting', ['name' => $notifiable->name]))
            ->line(__('mail.ticket_sla.intro', $vars));

        foreach (array_slice($this->tickets, 0, self::MAX_LISTED) as $ticket) {
            $mail->line(__('mail.ticket_sla.item', ['title' => $ticket['title'], 'project' => $ticket['project']]));
        }

        if ($count > self::MAX_LISTED) {
            $mail->line(__('mail.ticket_sla.more', ['count' => $count - self::MAX_LISTED]));
        }

        return $mail
            ->action(__('mail.ticket_sla.action'), route('admin.kanban'))
            ->line(__('mail.ticket_sla.outro'))
            ->salutation(__('mail.salutation'));
    }
}
