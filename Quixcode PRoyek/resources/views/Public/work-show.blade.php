<x-layouts::public :locale="$locale" :seo="$seo">
    <section class="bg-slate-950 py-16 text-white">
        <div class="mx-auto max-w-4xl px-4 sm:px-6">
            <a href="{{ route('public.works', ['locale' => $locale]) }}" class="text-sm font-medium text-emerald-400 hover:text-emerald-300">
                ← {{ __('public.works.back_to_works') }}
            </a>
            <h1 class="mt-4 font-heading text-3xl font-bold sm:text-4xl">{{ $study->localized('title', $locale) }}</h1>
            @if ($study->published_at)
                <p class="mt-2 text-xs text-slate-400">{{ $study->published_at->translatedFormat('d F Y') }}</p>
            @endif
        </div>
    </section>

    <article class="mx-auto max-w-4xl px-4 py-14 sm:px-6">
        @if ($study->cover_path)
            <img src="{{ Storage::url($study->cover_path) }}" alt="{{ $study->localized('title', $locale) }}" loading="lazy" decoding="async" class="mb-8 max-h-96 w-full rounded-xl object-cover" />
        @endif

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs sm:p-8">
            <h2 class="font-heading text-xl font-bold text-slate-900">{{ __('public.works.overview_heading') }}</h2>
            <p class="mt-3 whitespace-pre-line text-base leading-relaxed text-slate-700">{{ $study->localized('summary', $locale) }}</p>

            @if (is_array($study->tech_stack) && $study->tech_stack !== [])
                <h3 class="mt-6 font-heading text-sm font-semibold uppercase tracking-wider text-slate-500">{{ __('public.works.stack_heading') }}</h3>
                <div class="mt-2.5 flex flex-wrap gap-2">
                    @foreach ($study->tech_stack as $tech)
                        <span class="rounded-lg bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-800">{{ $tech }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="mt-8 rounded-xl bg-slate-950 p-6 text-white sm:flex sm:items-center sm:justify-between">
            <div>
                <h2 class="font-heading text-lg font-bold">{{ __('public.works.cta_heading') }}</h2>
                <p class="mt-1 text-sm text-slate-300">{{ __('public.works.cta_body') }}</p>
            </div>
            <a href="{{ route('public.start-project', ['locale' => $locale]) }}" class="mt-4 inline-flex min-h-11 shrink-0 items-center rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white sm:mt-0">
                {{ __('public.nav.start_project') }}
            </a>
        </div>
    </article>
</x-layouts::public>
