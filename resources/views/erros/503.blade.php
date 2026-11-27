@php($locale = in_array(app()->getLocale(), ['id', 'en'], true) ? app()->getLocale() : 'id')
<x-layouts::public :locale="$locale" :seo="['title' => '503 — Quix Code']">
    <section class="mx-auto max-w-2xl px-4 py-24 text-center sm:px-6">
        <p class="font-heading text-sm font-bold uppercase tracking-widest text-amber-600">503</p>
        <h1 class="mt-3 font-heading text-3xl font-bold text-slate-900">{{ __('public.errors.503.title') }}</h1>
        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ __('public.errors.503.body') }}</p>
    </section>
</x-layouts::public>
