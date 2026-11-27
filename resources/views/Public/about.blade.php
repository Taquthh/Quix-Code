<x-layouts::public :locale="$locale" :seo="$seo">
    <section class="bg-slate-950 py-16 text-white">
        <div class="mx-auto max-w-5xl px-4 sm:px-6">
            <h1 class="font-heading text-3xl font-bold sm:text-4xl">{{ __('public.about.title') }}</h1>
            <p class="mt-3 max-w-2xl text-slate-300">{{ __('public.about.subtitle') }}</p>
        </div>
    </section>

    <section class="mx-auto max-w-5xl space-y-8 px-4 py-14 sm:px-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs sm:p-8">
            <h2 class="font-heading text-xl font-bold text-slate-900">{{ __('public.about.story_title') }}</h2>
            <p class="mt-3 leading-relaxed text-slate-700">{{ __('public.about.story_body') }}</p>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            @foreach (['transparency', 'engineering', 'security'] as $value)
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
                    <h3 class="font-heading text-lg font-bold text-slate-900">{{ __('public.about.values.'.$value.'.title') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('public.about.values.'.$value.'.body') }}</p>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts::public>
