<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\RevisionTicket;
use App\Models\TicketComment;
use App\Models\User;
use App\Support\InAppNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email dan lonceng ke klien: tim membalas tiket. Isi balasan tidak ikut dikirim, klien membacanya di portal. */
class TicketRepliedNotification extends Notification
{
    public function __construct(
        public readonly TicketComment $comment,
        public readonly RevisionTicket $ticket,
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
        return (new MailMessage)
            ->subject(__('mail.ticket_replied.subject', ['ticket' => $this->ticket->title, 'project' => $this->project->title]))
            ->greeting(__('mail.greeting', ['name' => $notifiable->name]))
            ->line(__('mail.ticket_replied.intro', ['ticket' => $this->ticket->title, 'project' => $this->project->title]))
            ->line(__('mail.ticket_replied.hint'))
            ->action(__('mail.ticket_replied.action'), route('portal.projects.show', $this->project->id))
            ->salutation(__('mail.salutation'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return InAppNotification::payload('ticket_replied', $this->project, [
            'subject' => mb_substr($this->ticket->title, 0, 180),
        ], 'portal.projects.show');
    }
}
