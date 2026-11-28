<?php

namespace App\Livewire\Admin\Settings;

use App\Models\NotificationTemplate;
use App\Services\NotificationTemplateService;
use App\Support\NotificationTemplateCatalog as Catalog;
use App\Support\RenderedTemplate;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Editor template notifikasi (T5-13): per kunci, bahasa, dan kanal, dengan pratinjau dan riwayat versi. Hanya admin. */
#[Layout('layouts::admin')]
class NotificationTemplates extends Component
{
    public string $templateKey = '';

    public string $templateLocale = 'id';

    public string $templateChannel = 'email';

    public string $subject = '';

    public string $body = '';

    public function mount(): void
    {
        $this->authorize('admin');
        $this->templateKey = Catalog::keys()[0];
        $this->loadForm();
    }

    public function updatedTemplateKey(): void
    {
        $this->loadForm();
    }

    public function updatedTemplateLocale(): void
    {
        $this->loadForm();
    }

    public function updatedTemplateChannel(): void
    {
        $this->loadForm();
    }

    public function save(): void
    {
        $this->authorize('admin');
        $this->normalizeSelection();

        $service = app(NotificationTemplateService::class);
        $errors = $service->validate($this->templateKey, $this->templateLocale, $this->templateChannel, $this->subject, $this->body);

        if ($errors !== []) {
            foreach ($errors as $field => $message) {
                $this->addError($field === 'key' ? 'templateKey' : $field, $message);
            }

            return;
        }

        $before = $service->latest($this->templateKey, $this->templateLocale, $this->templateChannel)?->version;
        $saved = $service->save($this->templateKey, $this->templateLocale, $this->templateChannel, $this->subject, $this->body, auth()->user());
        $this->loadForm();

        Flux::toast(
            variant: $saved->version === $before ? 'warning' : 'success',
            text: $saved->version === $before
                ? __('admin.templates.unchanged')
                : __('admin.templates.saved', ['version' => $saved->version]),
        );
    }

    /** Isi form dengan teks bawaan; baru berlaku setelah disimpan sebagai versi baru. */
    public function useDefault(): void
    {
        $this->authorize('admin');
        $this->normalizeSelection();
        $this->resetValidation();

        $default = Catalog::defaults($this->templateKey, $this->templateLocale, $this->templateChannel);
        $this->subject = (string) $default['subject'];
        $this->body = $default['body'];
    }

    /** Isi form dengan isi salah satu versi lama; baru berlaku setelah disimpan sebagai versi baru. */
    public function restore(int $version): void
    {
        $this->authorize('admin');
        $this->normalizeSelection();

        $template = NotificationTemplate::query()
            ->where('key', $this->templateKey)
            ->where('locale', $this->templateLocale)
            ->where('channel', $this->templateChannel)
            ->where('version', $version)
            ->first();

        if ($template === null) {
            return;
        }

        $this->resetValidation();
        $this->subject = (string) $template->subject;
        $this->body = $template->body;
    }

    /** @return list<string> */
    #[Computed]
    public function variables(): array
    {
        return Catalog::variables($this->templateKey);
    }

    /** Penulisan variabel di template, mis. {{name}}. */
    public function token(string $variable): string
    {
        return '{{'.$variable.'}}';
    }

    #[Computed]
    public function preview(): RenderedTemplate
    {
        $sample = array_intersect_key(Catalog::sample($this->templateLocale), array_flip($this->variables()));

        return app(NotificationTemplateService::class)->render(
            $this->templateChannel === 'email' ? $this->subject : null,
            $this->body,
            $sample,
        );
    }

    /** @return Collection<int, NotificationTemplate> */
    #[Computed]
    public function versions(): Collection
    {
        return app(NotificationTemplateService::class)->versions($this->templateKey, $this->templateLocale, $this->templateChannel);
    }

    public function render(): View
    {
        return view('livewire.admin.settings.notification-templates', [
            'keys' => Catalog::keys(),
            'locales' => Catalog::LOCALES,
            'channels' => Catalog::CHANNELS,
        ]);
    }

    /** Pilihan dari klien tidak dipercaya: nilai di luar katalog dikembalikan ke bawaan. */
    private function normalizeSelection(): void
    {
        if (! Catalog::has($this->templateKey)) {
            $this->templateKey = Catalog::keys()[0];
        }

        if (! in_array($this->templateLocale, Catalog::LOCALES, true)) {
            $this->templateLocale = 'id';
        }

        if (! in_array($this->templateChannel, Catalog::CHANNELS, true)) {
            $this->templateChannel = 'email';
        }
    }

    /** Muat versi terbaru, atau teks bawaan bila belum pernah disimpan. */
    private function loadForm(): void
    {
        $this->normalizeSelection();
        $this->resetValidation();

        $current = app(NotificationTemplateService::class)->latest($this->templateKey, $this->templateLocale, $this->templateChannel);
        $source = $current !== null
            ? ['subject' => $current->subject, 'body' => $current->body]
            : Catalog::defaults($this->templateKey, $this->templateLocale, $this->templateChannel);

        $this->subject = (string) $source['subject'];
        $this->body = $source['body'];
    }
}
