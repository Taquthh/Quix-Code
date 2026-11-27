@props([
    'locale' => app()->getLocale(),
    'seo' => [],
])
@php
    $activeLocale = in_array($locale, ['id', 'en'], true) ? $locale : 'id';
    $title = $seo['title'] ?? 'Quix Code — Integrated Software Agency & Client Portal';
    $description = $seo['description'] ?? __('public.seo.home_description');
    $canonical = $seo['canonical'] ?? url()->current();
    $alternates = $seo['alternates'] ?? [
        'id' => url('/id'),
        'en' => url('/en'),
        'x-default' => url('/id'),
    ];
    $ogLocale = $seo['og_locale'] ?? ($activeLocale === 'en' ? 'en_US' : 'id_ID');
    $selectedCurrency = \App\Support\PublicCurrencyEstimator::selectedCurrency();
    $analyticsProvider = (string) config('services.analytics.provider', 'none');
    $analyticsDomain = (string) config('services.analytics.domain', '');
    $analyticsScript = (string) config('services.analytics.script_url', '');
@endphp
<!DOCTYPE html>
<html lang="{{ $activeLocale }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}" />
    <link rel="canonical" href="{{ $canonical }}" />

    @foreach ($alternates as $hreflang => $href)
        <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}" />
    @endforeach

    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="Quix Code" />
    <meta property="og:title" content="{{ $title }}" />
    <meta property="og:description" content="{{ $description }}" />
    <meta property="og:url" content="{{ $canonical }}" />
    <meta property="og:locale" content="{{ $ogLocale }}" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $title }}" />
    <meta name="twitter:description" content="{{ $description }}" />

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="preload" as="style" href="https://fonts.bunny.net/css?family=outfit:600,700|plus-jakarta-sans:400,500,600,700&display=swap" />
    <link href="https://fonts.bunny.net/css?family=outfit:600,700|plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if ($analyticsProvider === 'plausible' && $analyticsDomain !== '' && $analyticsScript !== '')
        <script defer data-domain="{{ $analyticsDomain }}" src="{{ $analyticsScript }}"></script>
    @elseif ($analyticsProvider === 'umami' && $analyticsDomain !== '' && $analyticsScript !== '')
        <script defer data-website-id="{{ $analyticsDomain }}" src="{{ $analyticsScript }}"></script>
    @endif

    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'ProfessionalService',
            'name' => 'Quix Code',
            'url' => url('/'.$activeLocale),
            'description' => $description,
            'availableLanguage' => ['Indonesian', 'English'],
            'areaServed' => 'Global',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased selection:bg-emerald-600 selection:text-white" x-data="{ mobileMenu: false }">
    <header class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/95 text-slate-100 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <a href="{{ route('public.home', ['locale' => $activeLocale]) }}" class="flex items-center gap-2.5 font-heading text-lg font-bold tracking-tight text-white">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600 text-sm font-bold text-white">QC</span>
                <span>Quix Code</span>
            </a>

            <nav aria-label="{{ __('public.nav.aria') }}" class="hidden items-center gap-6 text-sm font-medium text-slate-300 md:flex">
                <a href="{{ route('public.services', ['locale' => $activeLocale]) }}" class="transition hover:text-white">{{ __('public.nav.services') }}</a>
                <a href="{{ route('public.works', ['locale' => $activeLocale]) }}" class="transition hover:text-white">{{ __('public.nav.works') }}</a>
                <a href="{{ route('public.about', ['locale' => $activeLocale]) }}" class="transition hover:text-white">{{ __('public.nav.about') }}</a>
                <a href="{{ route('public.faq', ['locale' => $activeLocale]) }}" class="transition hover:text-white">{{ __('public.nav.faq') }}</a>
            </nav>

            <div class="hidden items-center gap-2.5 md:flex">
                {{-- Pemilih mata uang estimasi (T6-03) --}}
                <div class="inline-flex rounded-lg border border-slate-800 bg-slate-900 p-0.5 text-xs">
                    @foreach (['IDR', 'USD'] as $curr)
                        <form method="POST" action="{{ route('public.currency', $curr) }}">
                            @csrf
                            <button type="submit" class="min-h-8 rounded-md px-2.5 py-1 font-medium transition {{ $selectedCurrency->value === $curr ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:text-white' }}">
                                {{ $curr }}
                            </button>
                        </form>
                    @endforeach
                </div>

                {{-- Pemilih bahasa (T6-03) --}}
                <div class="inline-flex rounded-lg border border-slate-800 bg-slate-900 p-0.5 text-xs">
                    @foreach (['id' => 'ID', 'en' => 'EN'] as $code => $label)
                        <form method="POST" action="{{ route('locale.update', $code) }}">
                            @csrf
                            <button type="submit" class="min-h-8 rounded-md px-2.5 py-1 font-medium transition {{ $activeLocale === $code ? 'bg-slate-700 text-white' : 'text-slate-300 hover:text-white' }}">
                                {{ $label }}
                            </button>
                        </form>
                    @endforeach
                </div>

                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex min-h-11 items-center rounded-lg border border-slate-700 px-3.5 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-500 hover:text-white">
                        {{ __('public.nav.dashboard') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center rounded-lg px-3 py-2 text-sm font-medium text-slate-300 transition hover:text-white">
                        {{ __('public.nav.login') }}
                    </a>
                @endauth

                <a href="{{ route('public.start-project', ['locale' => $activeLocale]) }}" class="inline-flex min-h-11 items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500">
                    {{ __('public.nav.start_project') }}
                </a>
            </div>

            <button type="button" @click="mobileMenu = !mobileMenu" :aria-expanded="mobileMenu" aria-controls="public-mobile-menu" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] text-slate-200 transition hover:bg-white/[0.08] md:hidden" aria-label="{{ __('public.nav.aria') }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <div id="public-mobile-menu" x-show="mobileMenu" x-cloak class="border-t border-white/10 bg-slate-950 px-4 py-4 md:hidden">
            <div class="flex flex-col gap-3 text-sm">
                <a href="{{ route('public.services', ['locale' => $activeLocale]) }}" class="py-1.5 text-slate-200">{{ __('public.nav.services') }}</a>
                <a href="{{ route('public.works', ['locale' => $activeLocale]) }}" class="py-1.5 text-slate-200">{{ __('public.nav.works') }}</a>
                <a href="{{ route('public.about', ['locale' => $activeLocale]) }}" class="py-1.5 text-slate-200">{{ __('public.nav.about') }}</a>
                <a href="{{ route('public.faq', ['locale' => $activeLocale]) }}" class="py-1.5 text-slate-200">{{ __('public.nav.faq') }}</a>
                <div class="flex flex-wrap items-center gap-2 pt-2">
                    @foreach (['id' => 'ID', 'en' => 'EN'] as $code => $label)
                        <form method="POST" action="{{ route('locale.update', $code) }}">
                            @csrf
                            <button type="submit" class="min-h-10 rounded-md border border-slate-700 px-3 py-1.5 text-xs font-medium {{ $activeLocale === $code ? 'bg-slate-700 text-white' : 'text-slate-300' }}">
                                {{ $label }}
                            </button>
                        </form>
                    @endforeach
                    @foreach (['IDR', 'USD'] as $curr)
                        <form method="POST" action="{{ route('public.currency', $curr) }}">
                            @csrf
                            <button type="submit" class="min-h-10 rounded-md border border-slate-700 px-3 py-1.5 text-xs font-medium {{ $selectedCurrency->value === $curr ? 'bg-emerald-600 text-white' : 'text-slate-300' }}">
                                {{ $curr }}
                            </button>
                        </form>
                    @endforeach
                </div>
                <a href="{{ route('public.start-project', ['locale' => $activeLocale]) }}" class="mt-2 inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 font-semibold text-white">
                    {{ __('public.nav.start_project') }}
                </a>
            </div>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-800 bg-slate-950 text-slate-300">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-3">
            <div>
                <div class="flex items-center gap-2.5 font-heading text-lg font-bold text-white">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600 text-sm font-bold text-white">QC</span>
                    <span>Quix Code</span>
                </div>
                <p class="mt-3 text-sm leading-relaxed text-slate-400">{{ __('public.footer.tagline') }}</p>
            </div>
            <div>
                <h2 class="font-heading text-sm font-semibold uppercase tracking-wider text-white">{{ __('public.footer.navigation') }}</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('public.services', ['locale' => $activeLocale]) }}" class="hover:text-white">{{ __('public.nav.services') }}</a></li>
                    <li><a href="{{ route('public.works', ['locale' => $activeLocale]) }}" class="hover:text-white">{{ __('public.nav.works') }}</a></li>
                    <li><a href="{{ route('public.about', ['locale' => $activeLocale]) }}" class="hover:text-white">{{ __('public.nav.about') }}</a></li>
                    <li><a href="{{ route('public.faq', ['locale' => $activeLocale]) }}" class="hover:text-white">{{ __('public.nav.faq') }}</a></li>
                </ul>
            </div>
            <div>
                <h2 class="font-heading text-sm font-semibold uppercase tracking-wider text-white">{{ __('public.footer.legal') }}</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('public.privacy', ['locale' => $activeLocale]) }}" class="hover:text-white">{{ __('public.nav.privacy') }}</a></li>
                    <li><a href="{{ route('public.terms', ['locale' => $activeLocale]) }}" class="hover:text-white">{{ __('public.nav.terms') }}</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-white">{{ __('public.nav.portal_login') }}</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-slate-900 px-4 py-5 text-center text-xs text-slate-500">
            © {{ date('Y') }} Quix Code. {{ __('public.footer.rights') }}
        </div>
    </footer>
</body>
</html>
