<x-layouts::public :locale="$locale" :seo="$seo">
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:py-16">
            <div class="max-w-3xl space-y-3">
                <h1 class="font-heading text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                    {{ __('public.works.title') }}
                </h1>
                <p class="text-base leading-relaxed text-slate-600 sm:text-lg">
                    {{ __('public.works.subtitle') }}
                </p>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:py-16">
        @if ($works->isEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center">
                <h2 class="font-heading text-lg font-semibold text-slate-900">{{ __('public.works.empty_title') }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ __('public.works.empty_description') }}</p>
            </div>
        @else
            <div class="grid gap-6 md:grid-cols-3">
                @foreach ($works as $work)
                    <article class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-xs transition hover:border-emerald-300">
                        <div class="space-y-3">
                            <h2 class="font-heading text-lg font-semibold text-slate-900">
                                <a href="{{ route('public.works.show', ['locale' => $locale, 'slug' => $work->slug]) }}" class="hover:text-emerald-700">
                                    {{ $work->localized('title', $locale) }}
                                </a>
                            </h2>
                            <p class="text-sm leading-relaxed text-slate-600">
                                {{ $work->localized('summary', $locale) }}
                            </p>
                            @if (is_array($work->tech_stack) && $work->tech_stack !== [])
                                <div class="flex flex-wrap gap-1.5 pt-1">
                                    @foreach ($work->tech_stack as $tech)
                                        <span class="rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-700">
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

            <div class="mt-10">
                {{ $works->links() }}
            </div>
        @endif
    </section>
</x-layouts::public>
