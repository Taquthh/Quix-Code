<?php

namespace App\Notifications\Concerns;

use App\Support\RenderedTemplate;
use Illuminate\Notifications\Messages\MailMessage;

/** Pasangan UsesNotificationTemplate: template admin menggantikan subjek dan isi email, sapaan, tombol, dan salam tetap. */
trait AppliesTemplate
{
    protected ?RenderedTemplate $template = null;

    protected bool $mailSuppressed = false;

    public function suppressMail(): static
    {
        $this->mailSuppressed = true;

        return $this;
    }

    /** @return list<string> Lonceng lebih dulu agar tetap tersimpan walau email gagal atau dilewati. */
    public function deliveryChannels(): array
    {
        return $this->mailSuppressed ? ['database'] : ['database', 'mail'];
    }

    public function deliveryReference(): string
    {
        foreach (['payment', 'comment', 'invoice', 'quotation', 'project'] as $property) {
            if (isset($this->{$property}) && is_object($this->{$property}) && method_exists($this->{$property}, 'getKey')) {
                return $this->templateKey().':'.$this->{$property}->getKey();
            }
        }

        return $this->templateKey();
    }

    public function applyTemplate(RenderedTemplate $template): static
    {
        $this->template = $template;

        return $this;
    }

    /** Panggil di akhir toMail(); tanpa template, pesan bawaan dari lang/ dikembalikan apa adanya. */
    protected function withTemplate(MailMessage $mail): MailMessage
    {
        if ($this->template === null) {
            return $mail;
        }

        $mail->subject = (string) $this->template->subject;
        $mail->introLines = $this->template->paragraphs;
        $mail->outroLines = [];

        return $mail;
    }
}
