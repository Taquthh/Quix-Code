<?php

return [

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /* GitHub API (T5-08): token read-only dengan izin Contents/Metadata pada repo klien. */
    'github' => [
        'token' => env('GITHUB_TOKEN'),
        'api_url' => env('GITHUB_API_URL', 'https://api.github.com'),
    ],

    /*
     | WhatsApp (T5-10). driver: disabled (bawaan, hanya email) | log (pengembangan) | http (gateway pihak ketiga).
     | Bentuk permintaan HTTP ada di App\Services\Whatsapp\HttpWhatsappGateway.
     */
    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'disabled'),
        'url' => env('WHATSAPP_API_URL'),
        'token' => env('WHATSAPP_API_TOKEN'),
        'timeout' => (int) env('WHATSAPP_TIMEOUT', 5),
    ],

    /* Pembaruan kurs otomatis untuk estimasi harga publik (T6-09). */
    'exchange_rate' => [
        'url' => env('EXCHANGE_RATE_API_URL'),
        'key' => env('EXCHANGE_RATE_API_KEY'),
        'timeout' => (int) env('EXCHANGE_RATE_TIMEOUT', 8),
    ],

    /* Anti-spam CAPTCHA / Cloudflare Turnstile untuk form publik (T6-04). */
    'captcha' => [
        'driver' => env('CAPTCHA_DRIVER', 'none'),
        'site_key' => env('CAPTCHA_SITE_KEY'),
        'secret_key' => env('CAPTCHA_SECRET_KEY'),
    ],

    /* Analitik ramah privasi tanpa cookie pelacak (T6-07): none | plausible | umami. */
    'analytics' => [
        'provider' => env('ANALYTICS_PROVIDER', 'none'),
        'domain' => env('ANALYTICS_DOMAIN'),
        'script_url' => env('ANALYTICS_SCRIPT_URL'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
