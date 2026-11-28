<x-layouts::public :locale="$locale" :seo="$seo">
    <article class="mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:py-16">
        <a href="{{ route('public.works', ['locale' => $locale]) }}"
           class="inline-flex items-center text-sm font-medium text-slate-600 transition hover:text-slate-900">
            &larr; {{ __('public.works.back_to_works') }}
        </a>

        <header class="mt-6 space-y-4 border-b border-slate-200 pb-8">
            <h1 class="font-heading text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                {{ $study->localized('title', $locale) }}
            </h1>
            @if ($study->published_at !== null)
                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">
                    {{ $study->published_at->translatedFormat('d F Y') }}
                </p>
            @endif
        </header>

        <div class="mt-8 space-y-8">
            <section class="space-y-3">
                <h2 class="font-heading text-xl font-semibold text-slate-900">
                    {{ __('public.works.overview_heading') }}
                </h2>
                <p class="text-base leading-relaxed text-slate-700">
                    {{ $study->localized('summary', $locale) }}
                </p>
            </section>

            @if (is_array($study->tech_stack) && $study->tech_stack !== [])
                <section class="space-y-3">
                    <h2 class="font-heading text-xl font-semibold text-slate-900">
                        {{ __('public.works.stack_heading') }}
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($study->tech_stack as $tech)
                            <span class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-sm font-medium text-slate-800">
                                {{ $tech }}
                            </span>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-6 sm:p-8">
                <h2 class="font-heading text-xl font-bold text-slate-900">
                    {{ __('public.works.cta_heading') }}
                </h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-700">
                    {{ __('public.works.cta_body') }}
                </p>
                <div class="mt-5">
                    <a href="{{ route('public.start-project', ['locale' => $locale]) }}"
                       class="inline-flex min-h-11 items-center rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        {{ __('public.nav.start_project') }}
                    </a>
                </div>
            </section>
        </div>
    </article>
</x-layouts::public>
