@props([
    'name',
    'label' => null,
    'type' => 'text',
    'hint' => null,
    'error' => null,
    'loading' => false,
])

@php
    $id = $attributes->get('id', 'input-'.preg_replace('/[^A-Za-z0-9_-]/', '-', $name));
    $describedBy = collect([$hint ? $id.'-hint' : null, $error ? $id.'-error' : null])->filter()->implode(' ');
@endphp

<div class="space-y-1.5">
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-slate-800 dark:text-slate-200">{{ $label }}</label>
    @endif

    <div class="relative">
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
               @if ($error) aria-invalid="true" @endif
               @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
               {{ $attributes->except('id')->class([
                   'block min-h-11 w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-500 dark:bg-slate-950 dark:text-slate-50 dark:placeholder:text-slate-400',
                   'border-slate-300 dark:border-slate-700' => ! $error,
                   'border-rose-700 dark:border-rose-400' => $error,
                   'pe-10' => $loading,
                   'disabled:cursor-not-allowed disabled:opacity-60',
               ]) }} />
        @if ($loading)
            <span class="pointer-events-none absolute inset-y-0 end-3 flex items-center" role="status">
                <svg class="size-4 animate-spin text-slate-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
                </svg>
                <span class="sr-only">{{ __('ui.loading') }}</span>
            </span>
        @endif
    </div>

    @if ($hint)
        <p id="{{ $id }}-hint" class="text-xs text-slate-600 dark:text-slate-400">{{ $hint }}</p>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="text-xs font-medium text-rose-800 dark:text-rose-300">{{ $error }}</p>
    @endif
</div>
