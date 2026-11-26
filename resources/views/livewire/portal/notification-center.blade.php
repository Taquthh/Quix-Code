<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-col gap-1">
            <flux:heading size="xl">{{ __('notifications.title') }}</flux:heading>
            <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('notifications.subtitle') }}</p>
        </div>
        @if ($this->unread > 0)
            <flux:button class="min-h-11" wire:click="markAllAsRead" data-test="mark-all-read">{{ __('notifications.mark_all_read') }}</flux:button>
        @endif
    </div>

    <x-card :padded="false">
        @forelse ($this->items as $item)
            <div wire:key="notification-{{ $item['id'] }}" data-notification @class([
                'flex items-start gap-3 border-b border-slate-200 px-card py-3 last:border-b-0 dark:border-slate-800',
                'bg-emerald-50/60 dark:bg-emerald-500/10' => ! $item['read'],
            ])>
                <span @class(['mt-2 size-2.5 shrink-0 rounded-full', 'bg-rose-600' => ! $item['read'], 'bg-transparent' => $item['read']]) aria-hidden="true"></span>

                <button type="button" wire:click="open('{{ $item['id'] }}')" class="min-h-11 min-w-0 flex-1 text-start">
                    <span class="block break-words text-sm font-semibold text-slate-900 dark:text-slate-50">
                        @unless ($item['read'])<span class="sr-only">{{ __('notifications.unread') }}: </span>@endunless
                        {{ $item['title'] }}
                    </span>
                    @if ($item['body'] !== '')
                        <span class="mt-0.5 block break-words text-sm text-slate-600 dark:text-slate-400">{{ $item['body'] }}</span>
                    @endif
                    <span class="mt-1 block text-xs text-slate-500 dark:text-slate-500">{{ $item['time'] }}</span>
                </button>

                @unless ($item['read'])
                    <button type="button" wire:click="markAsRead('{{ $item['id'] }}')"
                            class="inline-flex min-h-11 shrink-0 items-center rounded-lg px-3 text-xs font-medium text-slate-600 hover:text-growth dark:text-slate-400">
                        {{ __('notifications.mark_read') }}
                    </button>
                @endunless
            </div>
        @empty
            <x-empty-state :title="__('notifications.empty')" :description="__('notifications.empty_hint')" />
        @endforelse
    </x-card>
</div>
