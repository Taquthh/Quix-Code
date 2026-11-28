<a href="{{ route('portal.notifications') }}" wire:navigate wire:poll.60s
   data-notification-bell data-unread="{{ $this->unread }}"
   aria-label="{{ trans_choice('notifications.bell_label', $this->unread, ['count' => $this->unread]) }}"
   class="relative inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
    <flux:icon name="bell" class="size-6" />
    @if ($this->unread > 0)
        <span data-notification-count
              class="absolute end-1 top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-rose-600 px-1 text-[0.7rem] font-semibold leading-5 text-white">
            {{ $this->unread > 99 ? '99+' : $this->unread }}
        </span>
    @endif
</a>
