<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

/** Verifikasi CAPTCHA / Cloudflare Turnstile pada form publik (T6-04). */
class CaptchaVerifier
{
    public function isEnabled(): bool
    {
        $driver = (string) config('services.captcha.driver', 'none');
        $secret = (string) config('services.captcha.secret_key', '');

        return $driver !== 'none' && trim($secret) !== '';
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        if (! is_string($token) || trim($token) === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post((string) config('services.captcha.verify_url'), array_filter([
                    'secret' => (string) config('services.captcha.secret_key'),
                    'response' => trim($token),
                    'remoteip' => $ip,
                ]));

            return $response->successful() && (bool) $response->json('success', false);
        } catch (Throwable) {
            return false;
        }
    }
}
