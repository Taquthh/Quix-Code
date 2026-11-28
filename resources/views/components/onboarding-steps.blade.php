@props(['steps'])

<x-card data-onboarding aria-labelledby="onboarding-heading">
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div class="min-w-0">
                <h2 id="onboarding-heading" class="font-heading text-base font-semibold text-slate-900 dark:text-slate-50">{{ __('help.onboarding.heading') }}</h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('help.onboarding.intro') }}</p>
            </div>
            <a href="{{ route('portal.help') }}" wire:navigate class="inline-flex min-h-11 items-center text-sm font-medium text-growth hover:underline">{{ __('help.link') }} →</a>
        </div>

        <ol class="flex flex-col gap-3">
            @foreach ($steps as $index => $step)
                @php
                    $url = $step['route'] !== null ? route($step['route'], $step['params']) : null;
                    $current = $step['state'] === 'current';
                    $done = $step['state'] === 'done';
                @endphp
                <li data-onboarding-step="{{ $step['key'] }}" data-state="{{ $step['state'] }}" @if ($current) aria-current="step" @endif
                    class="flex items-start gap-3 rounded-card border p-3 {{ $current ? 'border-amber-300 bg-amber-50 dark:border-amber-500/40 dark:bg-amber-500/10' : 'border-slate-200 dark:border-slate-700' }}">
                    <span aria-hidden="true" class="flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold {{ $done ? 'bg-emerald-700 text-white' : ($current ? 'bg-amber-500 text-white' : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200') }}">{{ $done ? '✓' : $index + 1 }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="break-words text-sm font-semibold text-slate-900 dark:text-slate-50">
                            {{ __("help.onboarding.steps.{$step['key']}.title") }}
                            <span class="sr-only">— {{ __("help.onboarding.state.{$step['state']}") }}</span>
                        </p>
                        <p class="mt-0.5 break-words text-sm text-slate-600 dark:text-slate-400">{{ __("help.onboarding.steps.{$step['key']}.description") }}</p>
                        @if ($current && $url)
                            <flux:button variant="primary" size="sm" class="mt-2 min-h-11" :href="$url" wire:navigate>{{ __("help.onboarding.steps.{$step['key']}.action") }}</flux:button>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</x-card>
