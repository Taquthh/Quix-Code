<x-layouts::public :locale="$locale" :seo="$seo">
    <section class="bg-slate-950 py-16 text-white">
        <div class="mx-auto max-w-4xl px-4 sm:px-6">
            <h1 class="font-heading text-3xl font-bold sm:text-4xl">{{ __('public.legal.privacy.title') }}</h1>
            <p class="mt-2 text-sm text-slate-300">{{ __('public.legal.version_label', ['version' => config('quix.legal.privacy_version')]) }}</p>
        </div>
    </section>

    <article class="mx-auto max-w-4xl space-y-6 px-4 py-14 sm:px-6">
        @foreach (__('public.legal.privacy.sections') as $section)
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
                <h2 class="font-heading text-lg font-bold text-slate-900">{{ $section['heading'] }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-700">{{ $section['body'] }}</p>
            </section>
        @endforeach

        <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-6 text-sm text-emerald-950">
            <h2 class="font-heading text-base font-bold">{{ __('public.legal.dpo_heading') }}</h2>
            <p class="mt-1">
                {{ __('public.legal.dpo_body', [
                    'email' => config('quix.legal.dpo_email'),
                    'years' => config('quix.legal.retention_years'),
                ]) }}
            </p>
        </section>
    </article>
</x-layouts::public>
