<x-layouts::public :locale="$locale" :seo="$seo">
    <section class="bg-slate-950 py-20 text-white sm:py-24">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <span class="inline-flex items-center rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3.5 py-1 text-xs font-semibold text-emerald-300">
                {{ __('public.home.badge') }}
            </span>
            <h1 class="mt-5 max-w-3xl font-heading text-4xl font-bold tracking-tight sm:text-5xl">
                {{ __('public.home.headline') }}
            </h1>
            <p class="mt-5 max-w-2xl text-base leading-relaxed text-slate-300 sm:text-lg">
                {{ __('public.home.subheadline') }}
            </p>
            <div class="mt-8 flex flex-wrap items-center gap-4">
                <a href="{{ route('public.start-project', ['locale' => $locale]) }}" class="inline-flex min-h-12 items-center rounded-xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-950/50 transition hover:bg-emerald-500">
                    {{ __('public.home.cta_primary') }}
                </a>
                <a href="{{ route('public.works', ['locale' => $locale]) }}" class="inline-flex min-h-12 items-center rounded-xl border border-slate-700 px-6 py-3 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:text-white">
                    {{ __('public.home.cta_secondary') }}
                </a>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <div class="max-w-2xl">
            <h2 class="font-heading text-2xl font-bold text-slate-900 sm:text-3xl">{{ __('public.home.pillars_title') }}</h2>
            <p class="mt-2 text-sm text-slate-600 sm:text-base">{{ __('public.home.pillars_subtitle') }}</p>
        </div>

        <div class="mt-8 grid gap-6 md:grid-cols-3">
            @foreach (['web', 'automation', 'portal'] as $pillar)
                <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
                    <h3 class="font-heading text-lg font-bold text-slate-900">{{ __('public.services.items.'.$pillar.'.title') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('public.services.items.'.$pillar.'.summary') }}</p>
                    <a href="{{ route('public.services', ['locale' => $locale]) }}" class="mt-4 inline-flex items-center text-sm font-semibold text-emerald-700 hover:text-emerald-600">
                        {{ __('public.home.learn_more') }} →
                    </a>
                </article>
            @endforeach
        </div>
    </section>

    <section class="border-t border-slate-200 bg-white py-16">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="font-heading text-2xl font-bold text-slate-900 sm:text-3xl">{{ __('public.home.works_title') }}</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ __('public.home.works_subtitle') }}</p>
                </div>
                <a href="{{ route('public.works', ['locale' => $locale]) }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-600">
                    {{ __('public.home.view_all_works') }} →
                </a>
            </div>

            @if ($featuredWorks->isEmpty())
                <div class="mt-8 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                    <p class="font-medium text-slate-800">{{ __('public.works.empty_title') }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ __('public.works.empty_description') }}</p>
                </div>
            @else
                <div class="mt-8 grid gap-6 md:grid-cols-3">
                    @foreach ($featuredWorks as $work)
                        <article class="flex flex-col justify-between rounded-xl border border-slate-200 bg-slate-50 p-6">
                            <div>
                                @if ($work->cover_path)
                                    <img src="{{ Storage::url($work->cover_path) }}" alt="{{ $work->localized('title', $locale) }}" loading="lazy" decoding="async" class="mb-4 h-44 w-full rounded-lg object-cover" />
                                @endif
                                <h3 class="font-heading text-lg font-bold text-slate-900">{{ $work->localized('title', $locale) }}</h3>
                                <p class="mt-2 text-sm text-slate-600">{{ $work->localized('summary', $locale) }}</p>
                                @if (is_array($work->tech_stack) && $work->tech_stack !== [])
                                    <div class="mt-4 flex flex-wrap gap-1.5">
                                        @foreach ($work->tech_stack as $tech)
                                            <span class="rounded-md bg-slate-200/80 px-2 py-0.5 text-xs font-medium text-slate-700">{{ $tech }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <a href="{{ route('public.works.show', ['locale' => $locale, 'slug' => $work->slug]) }}" class="mt-5 inline-flex items-center text-sm font-semibold text-emerald-700 hover:text-emerald-600">
                                {{ __('public.works.read_case') }} →
                            </a>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts::public>
