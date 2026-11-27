<x-layouts::public :locale="$locale" :seo="$seo">
    <section class="bg-slate-950 py-16 text-white">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <h1 class="font-heading text-3xl font-bold sm:text-4xl">{{ __('public.works.title') }}</h1>
            <p class="mt-3 max-w-2xl text-slate-300">{{ __('public.works.subtitle') }}</p>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
        @if ($works->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center">
                <h2 class="font-heading text-lg font-bold text-slate-900">{{ __('public.works.empty_title') }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ __('public.works.empty_description') }}</p>
                <a href="{{ route('public.start-project', ['locale' => $locale]) }}" class="mt-5 inline-flex min-h-11 items-center rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white">
                    {{ __('public.nav.start_project') }}
                </a>
            </div>
        @else
            <div class="grid gap-6 md:grid-cols-3">
                @foreach ($works as $work)
                    <article class="flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
                        <div>
                            @if ($work->cover_path)
                                <img src="{{ Storage::url($work->cover_path) }}" alt="{{ $work->localized('title', $locale) }}" loading="lazy" decoding="async" class="mb-4 h-44 w-full rounded-lg object-cover" />
                            @endif
                            <h2 class="font-heading text-lg font-bold text-slate-900">{{ $work->localized('title', $locale) }}</h2>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $work->localized('summary', $locale) }}</p>
                            @if (is_array($work->tech_stack) && $work->tech_stack !== [])
                                <div class="mt-4 flex flex-wrap gap-1.5">
                                    @foreach ($work->tech_stack as $tech)
                                        <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{{ $tech }}</span>
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

            <div class="mt-8">
                {{ $works->links() }}
            </div>
        @endif
    </section>
</x-layouts::public>
