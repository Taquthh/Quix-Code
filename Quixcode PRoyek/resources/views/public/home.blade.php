<x-layouts::public :locale="$locale" :seo="$seo">
    {{-- Hero Section --}}
    <section class="relative isolate overflow-hidden border-b border-slate-800 bg-slate-950 text-white" data-public-hero>
        <div class="relative mx-auto grid max-w-7xl items-center gap-14 px-4 py-20 sm:px-6 sm:py-24 lg:grid-cols-[1.1fr_.9fr] lg:gap-16 lg:px-8 lg:py-28">
            <div class="relative z-10 max-w-3xl space-y-7">
                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/25 bg-emerald-400/10 px-3.5 py-1.5 text-xs font-semibold tracking-wide text-emerald-200">
                    <span class="size-1.5 rounded-full bg-emerald-300 shadow-[0_0_12px_rgb(110_231_183_/_0.85)]" aria-hidden="true"></span>
                    {{ __('public.home.badge') }}
                </span>

                <h1 class="max-w-3xl font-heading text-4xl font-semibold leading-[1.08] tracking-[-0.045em] text-white text-balance sm:text-5xl lg:text-6xl xl:text-7xl">
                    {{ __('public.home.headline') }}
                </h1>

                <p class="max-w-2xl text-base leading-8 text-slate-300 sm:text-lg sm:leading-8">
                    {{ __('public.home.subheadline') }}
                </p>

                <div class="flex flex-col gap-3 pt-1 sm:flex-row sm:items-center">
                    <a href="{{ route('public.start-project', ['locale' => $locale]) }}"
                       class="group inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-emerald-500 px-6 py-3 text-sm font-semibold text-slate-950 shadow-[0_12px_36px_rgb(16_185_129_/_0.2)] transition hover:-translate-y-0.5 hover:bg-emerald-400 focus-visible:outline-white">
                        {{ __('public.home.cta_primary') }}
                        <svg class="size-4 transition-transform group-hover:translate-x-0.5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4.167 10h11.666M10 4.167 15.833 10 10 15.833" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                    <a href="{{ route('public.works', ['locale' => $locale]) }}"
                       class="inline-flex min-h-12 items-center justify-center rounded-xl border border-white/15 bg-white/[0.04] px-6 py-3 text-sm font-semibold text-slate-100 transition hover:border-white/30 hover:bg-white/[0.08]">
                        {{ __('public.home.cta_secondary') }}
                    </a>
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-xl lg:mx-0" aria-hidden="true">
                <div class="absolute -inset-8 rounded-[2.5rem] bg-emerald-500/10 blur-3xl"></div>
                <div class="relative overflow-hidden rounded-[1.75rem] border border-white/10 bg-slate-900/85 p-3 shadow-[0_32px_100px_rgb(0_0_0_/_0.45)] backdrop-blur-xl sm:p-4">
                    <div class="flex items-center justify-between border-b border-white/10 px-3 pb-3 pt-1">
                        <div class="flex items-center gap-2">
                            <span class="size-2 rounded-full bg-rose-400/80"></span><span class="size-2 rounded-full bg-amber-300/80"></span><span class="size-2 rounded-full bg-emerald-300/80"></span>
                        </div>
                        <span class="text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-500">QUIX / STUDIO</span>
                        <span class="size-5 rounded-md border border-white/10 bg-white/[0.04]"></span>
                    </div>
                    <div class="grid gap-3 p-2 sm:grid-cols-[.8fr_1.2fr] sm:p-3">
                        <div class="flex flex-col justify-between rounded-2xl border border-white/[0.07] bg-slate-950/70 p-4 sm:p-5">
                            <div>
                                <span class="text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-300">{{ __('public.nav.services') }}</span>
                                <div class="mt-5 space-y-4">
                                    @foreach (['web', 'automation', 'portal'] as $index => $pillar)
                                        <div class="flex items-center gap-3">
                                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-white/10 bg-white/[0.04] font-mono text-[10px] text-emerald-200">0{{ $index + 1 }}</span>
                                            <span class="text-xs font-medium leading-snug text-slate-200">{{ __('public.services.items.'.$pillar.'.title') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mt-8 flex items-center gap-2 border-t border-white/[0.08] pt-4 text-[10px] font-medium text-slate-500">
                                <span class="size-1.5 rounded-full bg-emerald-300"></span>
                                {{ __('public.home.badge') }}
                            </div>
                        </div>
                        <div class="flex min-h-64 flex-col justify-between rounded-2xl border border-emerald-300/15 bg-gradient-to-br from-emerald-400/[0.12] via-slate-900 to-slate-950 p-4 sm:min-h-80 sm:p-5">
                            <div class="flex items-start justify-between">
                                <span class="rounded-lg border border-white/10 bg-white/[0.06] px-2.5 py-1.5 text-[10px] font-medium text-slate-300">{{ __('public.home.pillars_title') }}</span>
                                <span class="flex size-8 items-center justify-center rounded-xl bg-emerald-400 text-slate-950 shadow-lg shadow-emerald-950/30">
                                    <svg class="size-4" viewBox="0 0 20 20" fill="none"><path d="M10 2.5v15M2.5 10h15M4.7 4.7l10.6 10.6m0-10.6L4.7 15.3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                                </span>
                            </div>
                            <div class="space-y-4 py-8">
                                <div class="h-2 w-2/3 rounded-full bg-white/75"></div>
                                <div class="h-2 w-full rounded-full bg-white/15"></div>
                                <div class="h-2 w-5/6 rounded-full bg-white/15"></div>
                                <div class="flex gap-2 pt-2">
                                    <span class="h-7 flex-1 rounded-lg border border-white/10 bg-white/[0.04]"></span>
                                    <span class="h-7 w-12 rounded-lg bg-emerald-400/80"></span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between border-t border-white/10 pt-4 text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                                <span>01</span><span class="size-1 rounded-full bg-emerald-300"></span><span>02</span><span class="size-1 rounded-full bg-emerald-300"></span><span>03</span>
                            </div>
                        </div>
                    </div>
                    <div class="mx-2 mb-2 flex items-center justify-between rounded-xl border border-white/[0.07] bg-white/[0.025] px-4 py-3">
                        <span class="text-[11px] font-medium text-slate-400">{{ __('public.home.works_title') }}</span>
                        <span class="flex items-center gap-1.5 text-[10px] font-semibold text-emerald-200"><span class="size-1.5 rounded-full bg-emerald-300"></span>{{ __('public.nav.works') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Three Core Pillars --}}
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:py-20">
        <div class="max-w-2xl space-y-2">
            <h2 class="font-heading text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                {{ __('public.home.pillars_title') }}
            </h2>
            <p class="text-base text-slate-600">
                {{ __('public.home.pillars_subtitle') }}
            </p>
        </div>

        <div class="mt-10 grid gap-6 md:grid-cols-3">
            @foreach (['web', 'automation', 'portal'] as $pillar)
                <article class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 shadow-[0_8px_32px_rgb(15_23_42_/_0.035)] transition duration-300 hover:-translate-y-1 hover:border-emerald-300 hover:shadow-[0_18px_48px_rgb(15_23_42_/_0.08)] sm:p-7">
                    <div class="space-y-3">
                        <h3 class="font-heading text-lg font-semibold text-slate-900">
                            {{ __('public.services.items.'.$pillar.'.title') }}
                        </h3>
                        <p class="text-sm leading-relaxed text-slate-600">
                            {{ __('public.services.items.'.$pillar.'.summary') }}
                        </p>
                    </div>
                    <div class="pt-6">
                        <a href="{{ route('public.services', ['locale' => $locale]) }}#{{ $pillar }}"
                           class="inline-flex items-center text-sm font-semibold text-emerald-700 transition hover:text-emerald-800">
                            {{ __('public.home.learn_more') }} &rarr;
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Featured Case Studies --}}
    <section class="border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:py-20">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div class="max-w-2xl space-y-2">
                    <h2 class="font-heading text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                        {{ __('public.home.works_title') }}
                    </h2>
                    <p class="text-base text-slate-600">
                        {{ __('public.home.works_subtitle') }}
                    </p>
                </div>

                <a href="{{ route('public.works', ['locale' => $locale]) }}"
                   class="inline-flex min-h-11 items-center text-sm font-semibold text-emerald-700 hover:text-emerald-800">
                    {{ __('public.home.view_all_works') }} &rarr;
                </a>
            </div>

            @if ($featuredWorks->isEmpty())
                <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 p-8 text-center">
                    <h3 class="font-heading text-base font-semibold text-slate-900">{{ __('public.works.empty_title') }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ __('public.works.empty_description') }}</p>
                </div>
            @else
                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    @foreach ($featuredWorks as $work)
                        <article class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-slate-50/70 p-6 transition hover:border-emerald-300 hover:bg-white">
                            <div class="space-y-3">
                                <h3 class="font-heading text-lg font-semibold text-slate-900">
                                    <a href="{{ route('public.works.show', ['locale' => $locale, 'slug' => $work->slug]) }}" class="hover:text-emerald-700">
                                        {{ $work->localized('title', $locale) }}
                                    </a>
                                </h3>
                                <p class="text-sm leading-relaxed text-slate-600">
                                    {{ $work->localized('summary', $locale) }}
                                </p>
                                @if (is_array($work->tech_stack) && $work->tech_stack !== [])
                                    <div class="flex flex-wrap gap-1.5 pt-1">
                                        @foreach ($work->tech_stack as $tech)
                                            <span class="rounded-md border border-slate-200 bg-white px-2 py-0.5 text-xs font-medium text-slate-700">
                                                {{ $tech }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="pt-6">
                                <a href="{{ route('public.works.show', ['locale' => $locale, 'slug' => $work->slug]) }}"
                                   class="inline-flex items-center text-sm font-semibold text-emerald-700 hover:text-emerald-800">
                                    {{ __('public.works.read_case') }} &rarr;
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts::public>
