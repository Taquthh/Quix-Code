@props([
    'variant' => 'primary', // primary | secondary | danger | ghost
    'size' => 'md',         // sm | md | lg
    'type' => 'button',
    'href' => null,
    'loading' => false,
    'disabled' => false,
])

@php
    // Semua varian lolos WCAG AA (lihat docs/contrast-report.md).
    $variants = [
        'primary' => 'bg-growth text-white hover:bg-emerald-800 focus-visible:outline-emerald-600',
        'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800',
        'danger' => 'bg-rose-700 text-white hover:bg-rose-800',
        'ghost' => 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800',
    ];
    $sizes = ['sm' => 'min-h-9 px-3 py-1.5 text-sm', 'md' => 'min-h-11 px-4 py-2 text-sm', 'lg' => 'min-h-12 px-6 py-3 text-base'];
    $classes = 'inline-flex max-w-full items-center justify-center gap-2 break-words rounded-lg text-center font-semibold transition disabled:cursor-not-allowed disabled:opacity-60 aria-disabled:cursor-not-allowed aria-disabled:opacity-60 '
        .($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
    $inactive = $disabled || $loading;
@endphp

@if ($href && ! $inactive)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" @disabled($inactive) @if ($loading) aria-busy="true" @endif {{ $attributes->class($classes) }}>
        @if ($loading)
            <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
            </svg>
            <span class="sr-only">{{ __('ui.loading') }}</span>
        @endif
        {{ $slot }}
    </button>
@endif
