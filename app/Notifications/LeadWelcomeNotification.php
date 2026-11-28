<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email sambutan prospek baru dari halaman Start a Project (T6-02).
 * Mengirim tautan atur-password bertanda tangan — tidak pernah mengirim kata sandi mentah.
 */
class LeadWelcomeNotification extends Notification
{
    public function __construct(
        public readonly Project $project,
        public readonly string $passwordSetupUrl,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('mail.lead_welcome.subject', ['project' => $this->project->title]))
            ->greeting(__('mail.greeting', ['name' => $notifiable->name]))
            ->line(__('mail.lead_welcome.intro', ['project' => $this->project->title]))
            ->line(__('mail.lead_welcome.setup_hint'))
            ->action(__('mail.lead_welcome.action'), $this->passwordSetupUrl)
            ->line(__('mail.lead_welcome.outro'))
            ->salutation(__('mail.salutation'));
    }
}
