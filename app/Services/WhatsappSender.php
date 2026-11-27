<?php

namespace App\Services;

use App\Contracts\WhatsappGateway;
use App\Models\NotificationLog;
use App\Models\User;
use App\Notifications\Contracts\UsesNotificationTemplate;
use App\Support\NotificationTemplateCatalog as Catalog;
use Throwable;

/**
 * Pengiriman WhatsApp (T5-10): hanya ke klien dengan opt-in aktif, template per bahasa (T5-13), dicatat di
 * notifications_log, anti-duplikat per referensi pengiriman, dan dilindungi circuit breaker.
 */
class WhatsappSender
{
    /** WhatsApp terkirim: email tidak perlu. */
    public const SENT = 'sent';

    /** Referensi yang sama sudah pernah terkirim lewat WhatsApp: jangan kirim apa pun lagi. */
    public const DUPLICATE = 'duplicate';

    /** Tidak dicoba (driver mati, tanpa nomor, atau tanpa opt-in): email berjalan seperti biasa. */
    public const NOT_ATTEMPTED = 'not_attempted';

    /** Dicoba atau dilewati karena sirkuit terbuka dan gagal: email menjadi cadangan. */
    public const FAILED = 'failed';

    public function __construct(private readonly WhatsappCircuitBreaker $breaker) {}

    public function enabled(): bool
    {
        return in_array(config('services.whatsapp.driver'), ['http', 'log'], true);
    }

    /** @return self::SENT|self::DUPLICATE|self::NOT_ATTEMPTED|self::FAILED */
    public function send(User $user, UsesNotificationTemplate $notification, string $locale): string
    {
        if (! $this->enabled() || ! $user->canReceiveWhatsapp()) {
            return self::NOT_ATTEMPTED;
        }

        $key = $notification->templateKey();
        $reference = $notification->deliveryReference();

        $alreadySent = NotificationLog::query()
            ->where('channel', 'whatsapp')
            ->where('reference', $reference)
            ->where('status', 'sent')
            ->exists();

        if ($alreadySent) {
            return self::DUPLICATE;
        }

        $log = NotificationLog::create([
            'user_id' => $user->id,
            'channel' => 'whatsapp',
            'template' => $key,
            'locale' => $locale,
            'reference' => $reference,
            'status' => 'queued',
        ]);

        if ($this->breaker->isOpen()) {
            $log->forceFill(['status' => 'skipped', 'error' => 'circuit_open'])->save();

            return self::FAILED;
        }

        try {
            $service = app(NotificationTemplateService::class);
            $template = $service->latest($key, $locale, 'whatsapp');
            $default = Catalog::defaults($key, $locale, 'whatsapp');
            $variables = $service->variablesFor($notification, $user, $locale);
            $message = $service->render(null, $template->body ?? $default['body'], $variables, $template->version ?? 0)->text();

            $log->forceFill(['template_version' => $template->version ?? null, 'attempts' => 1])->save();

            $messageId = app(WhatsappGateway::class)->send($this->normalizePhone((string) $user->whatsapp), $message, $reference);

            $this->breaker->recordSuccess();
            $log->forceFill(['status' => 'sent', 'sent_at' => now(), 'provider_message_id' => $messageId])->save();

            return self::SENT;
        } catch (Throwable $e) {
            report($e);

            $this->breaker->recordFailure();
            $log->forceFill(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 1000)])->save();

            return self::FAILED;
        }
    }

    /** Hanya digit; awalan 0 (nomor lokal Indonesia) menjadi 62. */
    public function normalizePhone(string $phone): string
    {
        $digits = (string) preg_replace('/\D+/', '', $phone);

        return str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits;
    }
}
