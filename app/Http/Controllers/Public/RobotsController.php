<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/** Robots.txt dinamis dengan URL sitemap absolut berdasarkan APP_URL (T6-05). */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $sitemapUrl = rtrim((string) config('app.url'), '/').'/sitemap.xml';
        $content = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /portal',
            'Disallow: /webhooks',
            'Disallow: /settings',
            'Disallow: /dashboard',
            'Sitemap: '.$sitemapUrl,
            '',
        ]);

        return response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
