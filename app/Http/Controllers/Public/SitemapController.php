<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use App\Models\CaseStudy;
use Illuminate\Http\Response;

/** Sitemap XML dinamis multi-bahasa (/id dan /en) beserta tautan hreflang (T6-05). */
class SitemapController extends Controller
{
    private const STATIC_PATHS = [
        '',
        '/services',
        '/works',
        '/about',
        '/faq',
        '/start-project',
        '/privacy',
        '/terms',
    ];

    public function __invoke(): Response
    {
        $entries = [];
        $now = now()->toAtomString();

        foreach (self::STATIC_PATHS as $path) {
            foreach (SetLocale::SUPPORTED as $locale) {
                $entries[] = [
                    'loc' => $this->absolutePublicUrl($locale.$path),
                    'lastmod' => $now,
                    'alternates' => [
                        'id' => $this->absolutePublicUrl('id'.$path),
                        'en' => $this->absolutePublicUrl('en'.$path),
                        'x-default' => $this->absolutePublicUrl('id'.$path),
                    ],
                ];
            }
        }

        $studies = CaseStudy::query()
            ->published()
            ->orderByDesc('published_at')
            ->get(['slug', 'updated_at', 'published_at']);

        foreach ($studies as $study) {
            $lastmod = ($study->updated_at ?? $study->published_at ?? now())->toAtomString();

            foreach (SetLocale::SUPPORTED as $locale) {
                $entries[] = [
                    'loc' => $this->absolutePublicUrl($locale.'/works/'.$study->slug),
                    'lastmod' => $lastmod,
                    'alternates' => [
                        'id' => $this->absolutePublicUrl('id/works/'.$study->slug),
                        'en' => $this->absolutePublicUrl('en/works/'.$study->slug),
                        'x-default' => $this->absolutePublicUrl('id/works/'.$study->slug),
                    ],
                ];
            }
        }

        /** @var view-string $sitemapView */
        $sitemapView = 'public.sitemap';
        $xml = view($sitemapView, ['entries' => $entries])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function absolutePublicUrl(string $path): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }
}
