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

/** Email ke klien: penawaran baru. Hanya total yang tampil, tidak pernah operasional atau margin. */
class QuotationSentNotification extends Notification implements UsesNotificationTemplate
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
        $mail = (new MailMessage)
            ->subject(__('mail.quotation_sent.subject', ['version' => $this->quotation->version, 'project' => $this->project->title]))
            ->greeting(__('mail.greeting', ['name' => $notifiable->name]))
            ->line(__('mail.quotation_sent.intro', ['version' => $this->quotation->version, 'project' => $this->project->title]))
            ->line(__('mail.quotation_sent.total', ['total' => $this->quotation->totalMoney()->format()]))
            ->line($this->validityLine())
            ->action(__('mail.quotation_sent.action'), route('portal.projects.negotiation', $this->project->id))
            ->line(__('mail.quotation_sent.outro'));

        return $this->withTemplate($mail->salutation(__('mail.salutation')));
    }

    public function templateKey(): string
    {
        return 'quotation_sent';
    }

    /** @return array<string, string> */
    public function templateVariables(User $notifiable): array
    {
        return [
            'name' => $notifiable->name,
            'project' => $this->project->title,
            'url' => route('portal.projects.negotiation', $this->project->id),
            'version' => (string) $this->quotation->version,
            'total' => $this->quotation->totalMoney()->format(),
            'validity' => $this->validityLine(),
        ];
    }

    private function validityLine(): string
    {
        return $this->quotation->valid_until !== null
            ? __('mail.quotation_sent.valid_until', ['date' => $this->quotation->valid_until->translatedFormat('j F Y')])
            : __('mail.quotation_sent.no_limit');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return InAppNotification::payload('quotation_sent', $this->project, [
            'version' => $this->quotation->version,
            'amount' => (int) $this->quotation->totalMoney()->amount,
            'currency' => $this->quotation->totalMoney()->currency->value,
        ], 'portal.projects.negotiation');
    }
}
