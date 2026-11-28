@props(['type' => 'info', 'title' => null]) {{-- info | success | warning | danger --}}

@php
    $tones = [
        'info' => 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-500/30 dark:bg-sky-500/15 dark:text-sky-300',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/15 dark:text-emerald-300',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/15 dark:text-amber-300',
        'danger' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/15 dark:text-rose-300',
    ];
    $role = $type === 'danger' ? 'alert' : 'status';
@endphp

<div role="{{ $role }}" {{ $attributes->class(['rounded-card border px-4 py-3 text-sm', $tones[$type] ?? $tones['info']]) }}>
    @if ($title)
        <p class="font-heading font-semibold">{{ $title }}</p>
    @endif
    <div @class(['break-words', 'mt-1' => $title])>{{ $slot }}</div>
</div>
