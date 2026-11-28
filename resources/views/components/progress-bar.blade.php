@props(['value' => 0, 'label' => null, 'size' => 'md', 'showValue' => true])

@php
    $percent = max(0, min(100, (int) $value));
    $height = ['sm' => 'h-1.5', 'md' => 'h-2.5', 'lg' => 'h-4'][$size] ?? 'h-2.5';
    $name = $label ?? __('ui.progress');
@endphp

<div {{ $attributes }}>
    @if ($label || $showValue)
        <div class="mb-1.5 flex items-center justify-between gap-2 text-sm">
            <span class="min-w-0 break-words font-medium text-slate-700 dark:text-slate-200">{{ $label }}</span>
            @if ($showValue)
                <span class="tabular-nums text-slate-600 dark:text-slate-300">{{ $percent }}%</span>
            @endif
        </div>
    @endif

    <div role="progressbar" aria-label="{{ $name }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}"
         class="w-full overflow-hidden rounded-full bg-slate-200/80 p-0.5 shadow-inner dark:bg-slate-800 {{ $height }}">
        <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-400 shadow-[0_0_10px_rgb(16_185_129_/_0.25)] transition-[width] duration-500" style="width: {{ $percent }}%"></div>
    </div>
</div>
