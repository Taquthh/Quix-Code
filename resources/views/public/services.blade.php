<x-layouts::public :locale="$locale" :seo="$seo">
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:py-16">
            <div class="max-w-3xl space-y-3">
                <h1 class="font-heading text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                    {{ __('public.services.title') }}
                </h1>
                <p class="text-base leading-relaxed text-slate-600 sm:text-lg">
                    {{ __('public.services.subtitle') }}
                </p>
            </div>

            @if ($selectedCurrency !== \App\Enums\Currency::IDR)
                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-900">
                    <span class="mr-1.5 inline-flex items-center rounded-md bg-amber-600 px-2 py-0.5 font-semibold text-white">
                        {{ __('public.currency.estimate_badge') }}
                    </span>
                    {{ __('public.currency.estimate_notice') }}
                </div>
            @endif
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:py-16">
        <div class="grid gap-8 lg:grid-cols-3">
            @foreach (['web', 'automation', 'portal'] as $key)
                @php
                    $tier = $tiers[$key];
                    /** @var list<string> $deliverables */
                    $deliverables = (array) __('public.services.items.'.$key.'.deliverables');
                @endphp
                <article id="{{ $key }}" class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-xs sm:p-8">
                    <div class="space-y-4">
                        <h2 class="font-heading text-xl font-bold text-slate-900">
                            {{ __('public.services.items.'.$key.'.title') }}
                        </h2>
                        <p class="text-sm leading-relaxed text-slate-600">
                            {{ __('public.services.items.'.$key.'.summary') }}
                        </p>

                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-medium uppercase tracking-wide text-slate-500">
                                    {{ __('public.services.starting_from') }}
                                </span>
                                @if ($tier['is_estimate'])
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                                        {{ __('public.currency.estimate_badge') }}
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1 font-heading text-2xl font-bold tabular-nums text-slate-900">
                                {{ $tier['money']->format($locale) }}
                            </p>
                            @if ($tier['is_estimate'])
                                <p class="mt-1 text-xs text-slate-500">
                                    @if ($tier['fetched_at'] !== null)
                                        {{ __('public.currency.updated_at', ['time' => $tier['fetched_at']->diffForHumans()]) }}
                                    @else
                                        {{ __('public.currency.no_rate_date') }}
                                    @endif
                                    @if ($tier['is_stale'])
                                        · {{ __('public.currency.stale_warning') }}
                                    @endif
                                </p>
                            @endif
                        </div>

                        <ul class="space-y-2.5 pt-2 text-sm text-slate-700">
                            @foreach ($deliverables as $item)
                                <li class="flex items-start gap-2.5">
                                    <span class="mt-1 inline-block size-1.5 shrink-0 rounded-full bg-emerald-600"></span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="pt-8">
                        <a href="{{ route('public.start-project', ['locale' => $locale]) }}"
                           class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            {{ __('public.services.request_quote') }}
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</x-layouts::public>
