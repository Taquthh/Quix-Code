@props(['label', 'value', 'hint' => null])

<x-card {{ $attributes->class('relative') }} data-stat-card>
    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ $label }}</p>
    <p class="mt-3 font-heading text-3xl font-semibold tracking-tight tabular-nums text-slate-950 dark:text-slate-50 sm:text-4xl">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ $hint }}</p>
    @endif
</x-card>
