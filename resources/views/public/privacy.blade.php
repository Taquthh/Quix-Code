<x-layouts::public :locale="$locale" :seo="$seo">
    <article class="mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:py-16">
        <header class="space-y-3 border-b border-slate-200 pb-6">
            <h1 class="font-heading text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                {{ __('public.legal.privacy.title') }}
            </h1>
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">
                {{ __('public.legal.version_label', ['version' => config('quix.legal.privacy_version', '2026.1')]) }}
            </p>
        </header>

        @php
            /** @var list<array{heading: string, body: string}> $sections */
            $sections = (array) __('public.legal.privacy.sections');
        @endphp

        <div class="mt-8 space-y-6">
            @foreach ($sections as $section)
                <section class="rounded-2xl border border-slate-200 bg-white p-6">
                    <h2 class="font-heading text-lg font-semibold text-slate-900">{{ $section['heading'] }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-slate-700">{{ $section['body'] }}</p>
                </section>
            @endforeach

            <section class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-6">
                <h2 class="font-heading text-lg font-semibold text-slate-900">{{ __('public.legal.dpo_heading') }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-700">
                    {{ __('public.legal.dpo_body', [
                        'email' => config('quix.legal.dpo_email', 'privacy@quixcode.my.id'),
                        'years' => config('quix.legal.retention_years', 5),
                    ]) }}
                </p>
            </section>
        </div>
    </article>
</x-layouts::public>
