<x-layouts::public :locale="$locale" :seo="$seo">
    {{-- Hero Section --}}
    <section class="relative overflow-hidden border-b border-slate-800 bg-slate-950 text-white">
        <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6 lg:py-28">
            <div class="max-w-3xl space-y-6">
                <span class="inline-flex items-center rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3.5 py-1 text-xs font-semibold tracking-wide text-emerald-300">
                    {{ __('public.home.badge') }}
                </span>

                <h1 class="font-heading text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                    {{ __('public.home.headline') }}
                </h1>

                <p class="text-lg leading-relaxed text-slate-300 sm:text-xl">
                    {{ __('public.home.subheadline') }}
                </p>

                <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:items-center">
                    <a href="{{ route('public.start-project', ['locale' => $locale]) }}"
                       class="inline-flex min-h-12 items-center justify-center rounded-xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500">
                        {{ __('public.home.cta_primary') }}
                    </a>
                    <a href="{{ route('public.works', ['locale' => $locale]) }}"
                       class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-700 bg-slate-900/70 px-6 py-3 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:text-white">
                        {{ __('public.home.cta_secondary') }}
                    </a>
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
                <article class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-xs transition hover:border-emerald-300">
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
