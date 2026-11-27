<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Notifications\Contracts\UsesNotificationTemplate;
use App\Support\ActivityLogger;
use App\Support\NotificationTemplateCatalog as Catalog;
use App\Support\RenderedTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Template notifikasi yang bisa diedit admin (T5-13). Setiap simpan membuat versi baru (versi lama tetap ada),
 * variabel di luar katalog ditolak, dan versi yang dipakai tercatat di notifications_log.template_version.
 */
class NotificationTemplateService
{
    private const MAX_SUBJECT = 190;

    private const MAX_BODY = ['email' => 5000, 'whatsapp' => 1000];

    /** Versi terbaru, atau null bila belum pernah disimpan (pengirim memakai teks bawaan lang/). */
    public function latest(string $key, string $locale, string $channel): ?NotificationTemplate
    {
        return NotificationTemplate::query()
            ->where('key', $key)
            ->where('locale', $locale)
            ->where('channel', $channel)
            ->orderByDesc('version')
            ->first();
    }

    /** @return Collection<int, NotificationTemplate> Terbaru lebih dulu. */
    public function versions(string $key, string $locale, string $channel): Collection
    {
        return NotificationTemplate::query()
            ->where('key', $key)
            ->where('locale', $locale)
            ->where('channel', $channel)
            ->orderByDesc('version')
            ->get();
    }

    /**
     * Pesan galat per kolom (kosong bila valid).
     *
     * @return array<string, string>
     */
    public function validate(string $key, string $locale, string $channel, ?string $subject, string $body): array
    {
        if (! Catalog::has($key) || ! in_array($locale, Catalog::LOCALES, true) || ! in_array($channel, Catalog::CHANNELS, true)) {
            return ['key' => __('admin.templates.errors.unknown_template')];
        }

        $allowed = Catalog::variables($key);
        $errors = [];
        $subject = trim((string) $subject);
        $body = trim($body);

        if ($channel === 'email') {
            if ($subject === '') {
                $errors['subject'] = __('admin.templates.errors.subject_required');
            } elseif (mb_strlen($subject) > self::MAX_SUBJECT) {
                $errors['subject'] = __('admin.templates.errors.subject_too_long', ['max' => self::MAX_SUBJECT]);
            } elseif (preg_match('/\R/u', $subject) === 1) {
                $errors['subject'] = __('admin.templates.errors.subject_single_line');
            } elseif (($message = $this->tokenError($subject, $allowed)) !== null) {
                $errors['subject'] = $message;
            }
        }

        if ($body === '') {
            $errors['body'] = __('admin.templates.errors.body_required');
        } elseif (mb_strlen($body) > self::MAX_BODY[$channel]) {
            $errors['body'] = __('admin.templates.errors.body_too_long', ['max' => self::MAX_BODY[$channel]]);
        } elseif (($message = $this->tokenError($body, $allowed)) !== null) {
            $errors['body'] = $message;
        }

        return $errors;
    }

    /**
     * Simpan sebagai versi baru. Isi yang sama dengan versi terbaru tidak menambah versi.
     *
     * @throws ValidationException
     */
    public function save(string $key, string $locale, string $channel, ?string $subject, string $body, ?User $actor = null): NotificationTemplate
    {
        $errors = $this->validate($key, $locale, $channel, $subject, $body);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $subject = $channel === 'email' ? trim((string) $subject) : null;
        $body = trim(str_replace(["\r\n", "\r"], "\n", $body));

        return DB::transaction(function () use ($key, $locale, $channel, $subject, $body, $actor): NotificationTemplate {
            $current = NotificationTemplate::query()
                ->where('key', $key)
                ->where('locale', $locale)
                ->where('channel', $channel)
                ->orderByDesc('version')
                ->lockForUpdate()
                ->first();

            if ($current !== null && $current->subject === $subject && $current->body === $body) {
                return $current;
            }

            $template = NotificationTemplate::query()->create([
                'key' => $key,
                'locale' => $locale,
                'channel' => $channel,
                'subject' => $subject,
                'body' => $body,
                'version' => ($current->version ?? 0) + 1,
            ]);

            ActivityLogger::log(ActivityAction::NotificationTemplateUpdated, $template, [
                'key' => $key,
                'locale' => $locale,
                'channel' => $channel,
                'version' => $template->version,
            ], $actor);

            return $template;
        });
    }

    /**
     * Buat versi 1 dari isi bawaan untuk setiap kunci, bahasa, dan kanal yang belum punya template.
     * Aman dijalankan ulang: template yang sudah ada (termasuk yang diedit admin) tidak disentuh.
     *
     * @return int jumlah template baru
     */
    public function seedDefaults(): int
    {
        $created = 0;

        foreach (Catalog::keys() as $key) {
            foreach (Catalog::LOCALES as $locale) {
                foreach (Catalog::CHANNELS as $channel) {
                    $exists = NotificationTemplate::query()
                        ->where('key', $key)->where('locale', $locale)->where('channel', $channel)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $default = Catalog::defaults($key, $locale, $channel);
                    NotificationTemplate::query()->create([
                        'key' => $key,
                        'locale' => $locale,
                        'channel' => $channel,
                        'subject' => $default['subject'],
                        'body' => $default['body'],
                        'version' => 1,
                    ]);
                    $created++;
                }
            }
        }

        return $created;
    }

    /**
     * Isi variabel dalam satu lintasan (nilai yang berisi {{...}} tidak diperluas lagi). Variabel yang tidak
     * dikenal menjadi kosong; paragraf kosong dibuang.
     *
     * @param  array<string, scalar|null>  $variables
     */
    public function render(?string $subject, string $body, array $variables, int $version = 0): RenderedTemplate
    {
        $fill = static fn (string $text): string => (string) preg_replace_callback(
            '/\{\{\s*([a-z_]+)\s*\}\}/',
            static fn (array $m): string => (string) ($variables[$m[1]] ?? ''),
            $text,
        );

        $paragraphs = preg_split('/\R{2,}/u', trim($fill($body))) ?: [];
        $paragraphs = array_values(array_filter(array_map('trim', $paragraphs), static fn (string $p): bool => $p !== ''));

        return new RenderedTemplate(
            $subject === null ? null : trim(preg_replace('/\s+/u', ' ', $fill($subject)) ?? ''),
            $paragraphs,
            $version,
        );
    }

    /**
     * Nilai variabel notifikasi untuk penerima, dihitung dalam bahasa penerima (bahasa aplikasi dipulihkan setelahnya).
     *
     * @return array<string, string>
     */
    public function variablesFor(UsesNotificationTemplate $notification, User $user, string $locale): array
    {
        $previous = app()->getLocale();
        app()->setLocale($locale);

        try {
            return $notification->templateVariables($user);
        } finally {
            app()->setLocale($previous);
        }
    }

    /** @param  array<string, scalar|null>  $variables */
    public function renderTemplate(NotificationTemplate $template, array $variables): RenderedTemplate
    {
        return $this->render($template->subject, $template->body, $variables, $template->version);
    }

    /**
     * @param  list<string>  $allowed
     */
    private function tokenError(string $text, array $allowed): ?string
    {
        $unknown = [];

        if (preg_match_all('/\{\{(.*?)\}\}/s', $text, $matches) > 0) {
            foreach ($matches[1] as $inner) {
                $name = trim($inner);

                if (preg_match('/^[a-z_]+$/', $name) !== 1 || ! in_array($name, $allowed, true)) {
                    $unknown[] = '{{'.mb_substr($name === '' ? ' ' : $name, 0, 40).'}}';
                }
            }
        }

        if ($unknown !== []) {
            return __('admin.templates.errors.unknown_variable', ['variables' => implode(', ', array_values(array_unique($unknown)))]);
        }

        $rest = (string) preg_replace('/\{\{.*?\}\}/s', '', $text);

        if (str_contains($rest, '{{') || str_contains($rest, '}}')) {
            return __('admin.templates.errors.unbalanced');
        }

        return null;
    }
}
