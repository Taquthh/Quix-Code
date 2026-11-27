<x-layouts::public :locale="$locale" :seo="$seo">
    <section class="bg-slate-950 py-16 text-white">
        <div class="mx-auto max-w-4xl px-4 sm:px-6">
            <h1 class="font-heading text-3xl font-bold sm:text-4xl">{{ __('public.faq.title') }}</h1>
            <p class="mt-3 text-slate-300">{{ __('public.faq.subtitle') }}</p>
        </div>
    </section>

    <section class="mx-auto max-w-4xl space-y-4 px-4 py-14 sm:px-6">
        @foreach (__('public.faq.items') as $item)
            <details class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
                <summary class="cursor-pointer font-heading text-base font-bold text-slate-900">{{ $item['q'] }}</summary>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $item['a'] }}</p>
            </details>
        @endforeach
    </section>
</x-layouts::public>
