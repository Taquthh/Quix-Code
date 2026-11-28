<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/** robots.txt dinamis agar tautan Sitemap berupa URL absolut sesuai domain aktif (T6-05). */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /portal',
            'Disallow: /webhooks',
            'Disallow: /settings',
            'Disallow: /dashboard',
            'Disallow: /login',
            'Disallow: /reset-password',
            '',
            'Sitemap: '.url('/sitemap.xml'),
            '',
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
