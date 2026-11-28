<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use App\Notifications\Concerns\AppliesTemplate;
use App\Notifications\Contracts\UsesNotificationTemplate;
use App\Support\InAppNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email ke klien: penawaran terkirim akan segera berakhir (pengingat T3-06). */
class QuotationExpiringNotification extends Notification implements UsesNotificationTemplate
{
    use AppliesTemplate;

    public function __construct(public readonly Quotation $quotation, public readonly Project $project) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $this->deliveryChannels();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $days = max(0, (int) $this->quotation->daysLeft());

        $mail = (new MailMessage)
            ->subject(__('mail.quotation_expiring.subject', ['version' => $this->quotation->version, 'project' => $this->project->title]))
            ->greeting(__('mail.greeting', ['name' => $notifiable->name]))
            ->line(__('mail.quotation_expiring.intro', [
                'version' => $this->quotation->version,
                'project' => $this->project->title,
                'date' => $this->dateLabel(),
                'when' => trans_choice('mail.quotation_expiring.days', $days, ['count' => $days]),
            ]))
            ->action(__('mail.quotation_expiring.action'), route('portal.projects.negotiation', $this->project->id))
            ->line(__('mail.quotation_expiring.outro'))
            ->salutation(__('mail.salutation'));

        return $this->withTemplate($mail);
    }

    private function dateLabel(): ?string
    {
        return $this->quotation->valid_until?->translatedFormat('j F Y');
    }

    public function templateKey(): string
    {
        return 'quotation_expiring';
    }

    /** @return array<string, string> */
    public function templateVariables(User $notifiable): array
    {
        $days = max(0, (int) $this->quotation->daysLeft());

        return [
            'name' => $notifiable->name,
            'project' => $this->project->title,
            'url' => route('portal.projects.negotiation', $this->project->id),
            'version' => (string) $this->quotation->version,
            'date' => (string) $this->dateLabel(),
            'when' => trans_choice('mail.quotation_expiring.days', $days, ['count' => $days]),
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return InAppNotification::payload('quotation_expiring', $this->project, [
            'version' => $this->quotation->version,
        ], 'portal.projects.negotiation');
    }
}
