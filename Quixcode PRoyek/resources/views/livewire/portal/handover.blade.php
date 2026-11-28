<div class="flex flex-col gap-6" data-handover-page>
    <div class="flex flex-col gap-2">
        <a href="{{ route('portal.projects.show', $this->project->id) }}" wire:navigate class="inline-flex min-h-11 items-center text-sm text-slate-600 hover:text-growth dark:text-slate-400">← {{ __('handover.back') }}</a>
        <flux:heading size="xl">{{ __('handover.title') }}</flux:heading>
        <p class="text-sm text-slate-600 dark:text-slate-400">{{ $this->project->title }}</p>
    </div>

    @php
        $state = $this->state;
        $locked = \App\Support\HandoverStatus::isLocked($state);
        $tone = match ($state) {
            \App\Support\HandoverStatus::READY => 'bg-emerald-50 text-emerald-900 dark:bg-emerald-500/15 dark:text-emerald-200',
            \App\Support\HandoverStatus::NOT_STARTED => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200',
            default => 'bg-amber-50 text-amber-900 dark:bg-amber-500/15 dark:text-amber-200',
        };
    @endphp

    <x-card>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-heading text-base font-semibold text-slate-900 dark:text-slate-50">{{ __('handover.status_heading') }}</h2>
            <x-badge-status :status="$this->project->status" />
        </div>

        <div role="status" data-handover-state="{{ $state }}" class="mt-4 rounded-lg px-4 py-3 text-sm {{ $tone }}">
            <p class="font-semibold">{{ __("handover.states.{$state}.title") }}</p>
            <p class="mt-1">{{ __("handover.states.{$state}.body") }}</p>
        </div>

        @if ($state === \App\Support\HandoverStatus::FINAL_UNPAID)
            <div class="mt-4">
                <a href="{{ route('portal.projects.invoices', $this->project->id) }}" wire:navigate
                   class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:border-growth hover:text-growth dark:border-slate-700 dark:text-slate-200">
                    {{ __('handover.view_invoices') }}
                </a>
            </div>
        @endif
    </x-card>

    @unless ($locked)
        <x-card data-handover-files>
            <h2 class="font-heading text-base font-semibold text-slate-900 dark:text-slate-50">{{ __('handover.files_heading') }}</h2>

            @if ($this->handover !== null)
                <p class="mt-2 text-xs text-slate-600 dark:text-slate-400">
                    {{ __('handover.method') }}: {{ $this->handover->delivery_method->label() }}
                    @if ($this->handover->expires_at !== null)
                        · {{ $this->handover->isExpired()
                            ? __('handover.expired')
                            : __('handover.expires', ['date' => $this->handover->expires_at->translatedFormat('d M Y')]) }}
                    @endif
                </p>
            @endif

            @forelse ($this->files as $file)
                <div wire:key="handover-file-{{ $file->id }}" class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4 first:mt-3 first:border-t-0 first:pt-0 dark:border-slate-800">
                    <div class="min-w-0">
                        <p class="break-words text-sm font-medium text-slate-900 dark:text-slate-50">{{ __('handover.file_label', ['type' => strtoupper(pathinfo($file->path, PATHINFO_EXTENSION) ?: 'file')]) }}</p>
                        <p class="text-xs text-slate-600 dark:text-slate-400">{{ \Illuminate\Support\Number::fileSize((int) $file->size) }} · {{ $file->created_at->translatedFormat('d M Y') }}</p>
                    </div>
                    <a href="{{ route('files.download', $file->id) }}"
                       class="inline-flex min-h-11 items-center rounded-lg bg-growth px-4 text-sm font-medium text-white hover:opacity-90">
                        {{ __('handover.download') }}
                    </a>
                </div>
            @empty
                <x-empty-state data-empty="handover-files" :title="__('handover.files_empty')" :description="__('handover.files_empty_hint')" />
            @endforelse
        </x-card>
    @endunless
</div>
