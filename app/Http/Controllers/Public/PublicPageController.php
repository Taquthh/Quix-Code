<?php

namespace App\Http\Controllers\Public;

use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use App\Models\CaseStudy;
use App\Support\Money;
use App\Support\PublicCurrencyEstimator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Pengendali halaman etalase publik bilingual (/id dan /en) beserta metadata SEO (T6-01, T6-03, T6-05, T6-08). */
class PublicPageController extends Controller
{
    public function home(Request $request, ?string $locale = null): View|RedirectResponse
    {
        $activeLocale = $this->resolveLocale($locale);

        if ($locale === null) {
            $query = $request->getQueryString();
            $target = '/'.$activeLocale.($query !== null && $query !== '' ? '?'.$query : '');

            return redirect()->to($target);
        }

        $featuredWorks = CaseStudy::query()
            ->published()
            ->latest('published_at')
            ->limit(3)
            ->get();

        return $this->renderView('public.home', [
            'locale' => $activeLocale,
            'featuredWorks' => $featuredWorks,
            'seo' => $this->seoFor($activeLocale, 'home', __('public.seo.home_title'), __('public.seo.home_description')),
        ]);
    }

    public function services(Request $request, string $locale): View
    {
        $currency = PublicCurrencyEstimator::selectedCurrency($request);

        $tiers = [
            'web' => PublicCurrencyEstimator::formatAmount(Money::of(15_000_000, Currency::IDR), $currency),
            'automation' => PublicCurrencyEstimator::formatAmount(Money::of(22_000_000, Currency::IDR), $currency),
            'portal' => PublicCurrencyEstimator::formatAmount(Money::of(28_000_000, Currency::IDR), $currency),
        ];

        return $this->renderView('public.services', [
            'locale' => $locale,
            'selectedCurrency' => $currency,
            'tiers' => $tiers,
            'seo' => $this->seoFor($locale, 'services', __('public.seo.services_title'), __('public.seo.services_description')),
        ]);
    }

    public function works(string $locale): View
    {
        $works = CaseStudy::query()
            ->published()
            ->latest('published_at')
            ->paginate(9);

        return $this->renderView('public.works', [
            'locale' => $locale,
            'works' => $works,
            'seo' => $this->seoFor($locale, 'works', __('public.seo.works_title'), __('public.seo.works_description')),
        ]);
    }

    public function workShow(string $locale, string $slug): View
    {
        $study = CaseStudy::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $title = $study->localized('title', $locale);
        $summary = $study->localized('summary', $locale);

        return $this->renderView('public.work-show', [
            'locale' => $locale,
            'study' => $study,
            'seo' => $this->seoFor($locale, 'works/'.$study->slug, $title.' — Quix Code', $summary),
        ]);
    }

    public function about(string $locale): View
    {
        return $this->renderView('public.about', [
            'locale' => $locale,
            'seo' => $this->seoFor($locale, 'about', __('public.seo.about_title'), __('public.seo.about_description')),
        ]);
    }

    public function faq(string $locale): View
    {
        return $this->renderView('public.faq', [
            'locale' => $locale,
            'seo' => $this->seoFor($locale, 'faq', __('public.seo.faq_title'), __('public.seo.faq_description')),
        ]);
    }

    public function privacy(string $locale): View
    {
        return $this->renderView('public.privacy', [
            'locale' => $locale,
            'seo' => $this->seoFor($locale, 'privacy', __('public.seo.privacy_title'), __('public.seo.privacy_description')),
        ]);
    }

    public function terms(string $locale): View
    {
        return $this->renderView('public.terms', [
            'locale' => $locale,
            'seo' => $this->seoFor($locale, 'terms', __('public.seo.terms_title'), __('public.seo.terms_description')),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renderView(string $view, array $data = []): View
    {
        /** @var view-string $view */
        return view($view, $data);
    }

    private function resolveLocale(?string $locale): string
    {
        if (is_string($locale) && in_array($locale, SetLocale::SUPPORTED, true)) {
            return $locale;
        }

        $current = app()->getLocale();

        return in_array($current, SetLocale::SUPPORTED, true) ? $current : 'id';
    }

    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     canonical: string,
     *     alternates: array<string, string>,
     *     og_locale: string
     * }
     */
    private function seoFor(string $locale, string $page, string $title, string $description): array
    {
        $suffix = $page === 'home' ? '' : '/'.ltrim($page, '/');
        $canonical = url('/'.$locale.$suffix);

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'alternates' => [
                'id' => url('/id'.$suffix),
                'en' => url('/en'.$suffix),
                'x-default' => url('/id'.$suffix),
            ],
            'og_locale' => $locale === 'en' ? 'en_US' : 'id_ID',
        ];
    }
}
