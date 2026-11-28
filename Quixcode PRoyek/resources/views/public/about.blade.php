<x-layouts::public :locale="$locale" :seo="$seo">
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-5xl px-4 py-14 sm:px-6 lg:py-16">
            <div class="max-w-3xl space-y-3">
                <h1 class="font-heading text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                    {{ __('public.about.title') }}
                </h1>
                <p class="text-base leading-relaxed text-slate-600 sm:text-lg">
                    {{ __('public.about.subtitle') }}
                </p>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-5xl space-y-12 px-4 py-14 sm:px-6 lg:py-16">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs sm:p-8">
            <h2 class="font-heading text-2xl font-bold text-slate-900">
                {{ __('public.about.story_title') }}
            </h2>
            <p class="mt-3 text-base leading-relaxed text-slate-700">
                {{ __('public.about.story_body') }}
            </p>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            @foreach (['transparency', 'engineering', 'security'] as $val)
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                    <h3 class="font-heading text-lg font-semibold text-slate-900">
                        {{ __('public.about.values.'.$val.'.title') }}
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        {{ __('public.about.values.'.$val.'.body') }}
                    </p>
                </article>
            @endforeach
        </div>

        @php($team = (array) config('quix.team', []))
        @if ($team !== [])
            <div>
                <div class="max-w-2xl space-y-2">
                    <h2 class="font-heading text-2xl font-bold text-slate-900">{{ __('public.about.team_title') }}</h2>
                    <p class="text-base text-slate-600">{{ __('public.about.team_subtitle') }}</p>
                </div>
                <div class="mt-6 grid gap-6 md:grid-cols-3">
                    @foreach ($team as $member)
                        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                            <h3 class="font-heading text-lg font-semibold text-slate-900">{{ $member['name'] ?? '' }}</h3>
                            <p class="mt-0.5 text-xs font-semibold uppercase tracking-wide text-emerald-700">
                                {{ is_array($member['role'] ?? null) ? ($member['role'][$locale] ?? $member['role']['id'] ?? '') : ($member['role'] ?? '') }}
                            </p>
                            <p class="mt-3 text-sm leading-relaxed text-slate-600">
                                {{ is_array($member['bio'] ?? null) ? ($member['bio'][$locale] ?? $member['bio']['id'] ?? '') : ($member['bio'] ?? '') }}
                            </p>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
</x-layouts::public>
