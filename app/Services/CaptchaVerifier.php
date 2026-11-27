<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

/** Verifikasi CAPTCHA / Cloudflare Turnstile pada form publik (T6-04). */
class CaptchaVerifier
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function isConfigured(): bool
    {
        $driver = (string) config('services.captcha.driver', 'none');

        if ($driver === 'none') {
            return ! app()->environment('production');
        }

        return $driver === 'turnstile'
            && filled(config('services.captcha.site_key'))
            && filled(config('services.captcha.secret_key'));
    }

    public function shouldRenderWidget(): bool
    {
        return (string) config('services.captcha.driver', 'none') === 'turnstile'
            && filled(config('services.captcha.site_key'));
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        $driver = (string) config('services.captcha.driver', 'none');

        if ($driver === 'none') {
            return ! app()->environment('production');
        }

        if (! $this->isConfigured() || ! is_string($token) || trim($token) === '') {
            return false;
        }

        try {
            $response = Http::acceptJson()
                ->asForm()
                ->connectTimeout(3)
                ->timeout(5)
                ->withOptions(['allow_redirects' => false])
                ->post(self::VERIFY_URL, array_filter([
                    'secret' => (string) config('services.captcha.secret_key'),
                    'response' => trim($token),
                    'remoteip' => $ip,
                ], static fn (mixed $value): bool => $value !== null && $value !== ''));

            $payload = $response->json();

            return $response->successful()
                && is_array($payload)
                && ($payload['success'] ?? false) === true
                && ($payload['action'] ?? null) === 'public_lead_intake';
        } catch (Throwable) {
            return false;
        }
    }
}
