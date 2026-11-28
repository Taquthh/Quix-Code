@php
    // Bahasa dari segmen URL (/en/...), karena middleware web tidak berjalan pada sebagian halaman error.
    $segment = request()->segment(1);
    $locale = in_array($segment, ['id', 'en'], true) ? $segment : (in_array(app()->getLocale(), ['id', 'en'], true) ? app()->getLocale() : 'id');
    app()->setLocale($locale);
@endphp
<x-layouts::public :locale="$locale" :simple="true" :seo="['title' => '404 — Quix Code']">
    <section class="mx-auto max-w-2xl px-4 py-24 text-center sm:px-6">
        <p class="font-heading text-sm font-bold uppercase tracking-widest text-emerald-600">404</p>
        <h1 class="mt-3 font-heading text-3xl font-bold text-slate-900">{{ __('public.errors.404.title') }}</h1>
        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ __('public.errors.404.body') }}</p>
        <a href="{{ route('public.home', ['locale' => $locale]) }}" class="mt-6 inline-flex min-h-11 items-center rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white">
            {{ __('public.errors.back_home') }}
        </a>
    </section>
</x-layouts::public>
