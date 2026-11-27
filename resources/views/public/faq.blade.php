<x-layouts::public :locale="$locale" :seo="$seo">
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:py-16">
            <div class="space-y-3">
                <h1 class="font-heading text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                    {{ __('public.faq.title') }}
                </h1>
                <p class="text-base leading-relaxed text-slate-600 sm:text-lg">
                    {{ __('public.faq.subtitle') }}
                </p>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:py-16">
        @php
            /** @var list<array{q: string, a: string}> $items */
            $items = (array) __('public.faq.items');
        @endphp
        <div class="space-y-4">
            @foreach ($items as $item)
                <details class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-xs" @if ($loop->first) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-heading text-base font-semibold text-slate-900">
                        <span>{{ $item['q'] }}</span>
                        <span class="text-emerald-600 transition group-open:rotate-45">+</span>
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">
                        {{ $item['a'] }}
                    </p>
                </details>
            @endforeach
        </div>
    </section>
</x-layouts::public>
