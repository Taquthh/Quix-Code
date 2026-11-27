<x-layouts::public :locale="$locale" :seo="$seo">
    <section class="bg-slate-950 py-16 text-white">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <h1 class="font-heading text-3xl font-bold sm:text-4xl">{{ __('public.services.title') }}</h1>
            <p class="mt-3 max-w-2xl text-slate-300">{{ __('public.services.subtitle') }}</p>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
        @if ($selectedCurrency->value !== 'IDR')
            <div class="mb-8 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <strong>{{ __('public.currency.estimate_badge') }}:</strong>
                {{ __('public.currency.estimate_notice') }}
                @php($sampleTier = $tiers['web'])
                @if ($sampleTier['fetched_at'])
                    ({{ __('public.currency.updated_at', ['time' => $sampleTier['fetched_at']->diffForHumans()]) }})
                @endif
                @if ($sampleTier['is_stale'])
                    — <span class="font-semibold">{{ __('public.currency.stale_warning') }}</span>
                @endif
            </div>
        @endif

        <div class="grid gap-6 md:grid-cols-3">
            @foreach (['web', 'automation', 'portal'] as $key)
                @php($tier = $tiers[$key])
                <article class="flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
                    <div>
                        <h2 class="font-heading text-xl font-bold text-slate-900">{{ __('public.services.items.'.$key.'.title') }}</h2>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('public.services.items.'.$key.'.summary') }}</p>

                        <div class="mt-5 rounded-lg bg-slate-50 p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                                {{ __('public.services.starting_from') }}
                                @if ($tier['is_estimate'])
                                    <span class="ms-1 rounded bg-amber-100 px-1.5 py-0.5 text-[11px] font-semibold text-amber-800">{{ __('public.currency.estimate_badge') }}</span>
                                @endif
                            </p>
                            <p class="mt-1 font-heading text-2xl font-bold tabular-nums text-slate-900">{{ $tier['money']->format() }}</p>
                        </div>

                        <ul class="mt-5 space-y-2 text-sm text-slate-700">
                            @foreach (__('public.services.items.'.$key.'.deliverables') as $item)
                                <li class="flex items-start gap-2">
                                    <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-600"></span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <a href="{{ route('public.start-project', ['locale' => $locale]) }}" class="mt-6 inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-500">
                        {{ __('public.services.request_quote') }}
                    </a>
                </article>
            @endforeach
        </div>
    </section>
</x-layouts::public>
