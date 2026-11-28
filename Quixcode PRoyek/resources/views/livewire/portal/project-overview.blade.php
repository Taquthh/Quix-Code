<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <a href="{{ route('portal.overview') }}" wire:navigate class="inline-flex min-h-11 items-center text-sm text-slate-600 hover:text-growth dark:text-slate-400">← {{ __('portal.title') }}</a>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <flux:heading size="xl">{{ $project->title }}</flux:heading>
                <x-badge-status :status="$project->status" />
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:button size="sm" class="min-h-11" :href="route('portal.projects.invoices', $project->id)" wire:navigate>
                    {{ __('payments.invoices_link') }}
                </flux:button>
                <flux:button size="sm" class="min-h-11" :href="route('portal.projects.negotiation', $project->id)" wire:navigate>
                    {{ __('portal.quotation_chat') }}
                </flux:button>
            </div>
        </div>
    </div>

    @if ($actions !== [])
        <div class="flex flex-col gap-3">
            <x-next-action :action="$actions[0]" />
            @if (count($actions) > 1)
                <div class="flex flex-wrap gap-2">
                    @foreach (array_slice($actions, 1) as $extra)
                        <x-next-action :action="$extra" compact />
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <x-card :title="__('portal.project_progress')">
        <div class="flex items-center gap-4">
            <div class="min-w-0 flex-1"><x-progress-bar :value="$project->progress_percent" size="lg" :show-value="false" /></div>
            <span class="font-heading text-2xl font-semibold tabular-nums text-slate-900 dark:text-slate-50">{{ $project->progress_percent }}%</span>
        </div>
    </x-card>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-card :title="__('portal.milestones')" data-section="milestones">
            @if (session()->has('portal-milestone-accepted'))
                <div role="status" data-milestone-flash class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
                    {{ __('portal.milestone_accepted_flash', ['title' => session('portal-milestone-accepted')]) }}
                </div>
            @endif

            @if ($milestones->isEmpty())
                <x-empty-state :title="__('portal.no_milestones')" :description="__('portal.no_milestones_hint')" class="py-6" />
            @else
                <ol class="flex flex-col divide-y divide-slate-200 dark:divide-slate-800">
                    @foreach ($milestones as $milestone)
                        <li wire:key="portal-milestone-{{ $milestone->id }}" class="flex flex-wrap items-start justify-between gap-x-4 gap-y-1 py-3 first:pt-0 last:pb-0">
                            <div class="min-w-0">
                                <p class="break-words font-medium text-slate-900 dark:text-slate-50">{{ $milestone->title }}</p>
                                <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">
                                    {{ __('portal.milestone_target', ['percent' => $milestone->target_progress]) }}
                                    @if ($milestone->due_date) · {{ __('portal.milestone_due', ['date' => $milestone->due_date->translatedFormat('d M Y')]) }} @endif
                                </p>
                            </div>
                            <x-badge-status :status="$milestone->status" />

                            @php($acceptance = $milestone->acceptances->first())
                            @if ($acceptance)
                                <p class="basis-full text-xs font-medium text-emerald-700 dark:text-emerald-300" data-accepted="{{ $milestone->id }}">
                                    ✓ {{ __('portal.milestone_accepted_at', ['date' => $acceptance->accepted_at->translatedFormat('d M Y H:i')]) }}
                                </p>
                            @elseif (auth()->user()->can('accept', $milestone))
                                <div class="basis-full">
                                    <flux:button size="sm" variant="primary" icon="check" class="min-h-11" data-accept="{{ $milestone->id }}"
                                                 wire:click="acceptMilestone({{ $milestone->id }})" wire:loading.attr="disabled" wire:target="acceptMilestone"
                                                 wire:confirm="{{ __('portal.milestone_accept_confirm', ['title' => $milestone->title]) }}">
                                        {{ __('portal.milestone_accept') }}
                                    </flux:button>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-card>

        <x-card :title="__('portal.invoices')">
            <x-invoice-list :invoices="$invoices" />
        </x-card>
    </div>

    <x-card :title="__('portal.timeline')" data-section="timeline">
        @if ($commits->isEmpty())
            <x-empty-state data-empty="timeline" :title="__('portal.timeline_empty')" :description="__('portal.timeline_empty_hint')" class="py-6" />
        @else
            <ol class="flex flex-col gap-4 border-s border-slate-200 ps-4 dark:border-slate-800">
                @foreach ($commits as $commit)
                    <li wire:key="portal-commit-{{ $commit->id }}" class="relative">
                        <span class="absolute -start-[1.4rem] top-1.5 size-2.5 rounded-full bg-growth" aria-hidden="true"></span>
                        <p class="break-words text-sm text-slate-900 dark:text-slate-50">{{ $commit->commit_message !== '' ? $commit->commit_message : __('portal.timeline_fallback') }}</p>
                        <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">
                            {{ $commit->pushed_at->translatedFormat('d M Y H:i') }}
                            @if ($commit->progress_recorded !== null) · {{ __('portal.timeline_progress', ['percent' => $commit->progress_recorded]) }} @endif
                        </p>
                    </li>
                @endforeach
            </ol>
        @endif
    </x-card>

    <x-card :title="__('portal.client_requests')">
        @if (session()->has('portal-request-created'))
            <div role="status" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
                {{ __('portal.request_created') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,0.8fr)]">
            <div>
                @forelse ($tickets as $ticket)
                    <article wire:key="portal-ticket-{{ $ticket->id }}" class="border-b border-slate-200 py-4 first:pt-0 last:border-0 dark:border-slate-800">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="font-medium text-slate-900 dark:text-slate-50">{{ $ticket->title }}</h3>
                            <x-badge-status :status="$ticket->status" />
                        </div>
                        <p class="mt-2 whitespace-pre-line text-sm text-slate-600 dark:text-slate-400">{{ $ticket->description }}</p>
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            {{ __('enums.ticket_category.'.$ticket->category->value) }} · {{ __('enums.ticket_priority.'.$ticket->priority->value) }}
                            · {{ $ticket->milestone?->title ?? __('portal.ticket_general') }}
                            @if ($ticket->revision_round > 0) · {{ __('portal.ticket_round', ['round' => $ticket->revision_round]) }} @endif
                        </p>
                        @if ($ticket->is_billable)
                            <p data-ticket-billable class="mt-2 inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/30">{{ __('portal.ticket_billable') }}</p>
                        @endif
                        @if ($ticket->screenshot_path)
                            <p class="mt-2">
                                <a href="{{ route('tickets.screenshot', $ticket->id) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center text-sm font-medium text-growth hover:underline">{{ __('portal.ticket_screenshot') }}</a>
                            </p>
                        @endif
                    </article>
                @empty
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('portal.no_requests') }}</p>
                @endforelse
            </div>

            <form wire:submit="submitRequest" class="flex flex-col gap-4 rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                <h3 class="font-medium text-slate-900 dark:text-slate-50">{{ __('portal.new_request') }}</h3>
                <flux:input wire:model="title" :label="__('portal.request_title')" required maxlength="120" />
                <flux:textarea wire:model="description" :label="__('portal.request_description')" required rows="4" maxlength="5000" />
                <flux:select wire:model.live="category" :label="__('portal.request_category')">
                    @foreach (\App\Enums\TicketCategory::cases() as $option)
                        <flux:select.option :value="$option->value">{{ $option->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="milestone" :label="__('portal.request_milestone')">
                    <flux:select.option value="">{{ __('portal.ticket_general') }}</flux:select.option>
                    @foreach ($milestones as $option)
                        <flux:select.option :value="(string) $option->id">{{ $option->title }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="priority" :label="__('portal.request_priority')">
                    @foreach (\App\Enums\TicketPriority::cases() as $option)
                        <flux:select.option :value="$option->value">{{ $option->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                @if ($quota !== null)
                    @if ($quota['remaining'] > 0)
                        <p role="status" data-revision-quota="available" class="rounded-lg bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:bg-sky-500/15 dark:text-sky-200">{{ __('portal.quota_remaining', ['remaining' => $quota['remaining'], 'free' => $quota['free']]) }}</p>
                    @else
                        <p role="alert" data-revision-quota="exhausted" class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-500/15 dark:text-amber-200">{{ __('portal.quota_exhausted') }}</p>
                    @endif
                @else
                    <p data-revision-quota="bug" class="text-xs text-slate-600 dark:text-slate-400">{{ __('portal.quota_bug') }}</p>
                @endif

                <div>
                    <flux:input type="file" wire:model="screenshot" :label="__('portal.request_screenshot')" accept="image/jpeg,image/png,image/webp" />
                    <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('portal.request_screenshot_hint', ['max' => \App\Support\SecureUpload::maxMegabytes(\App\Support\SecureUpload::KIND_IMAGE)]) }}</p>
                    @error('screenshot') <p role="alert" class="mt-1 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="submitRequest,screenshot">
                        {{ __('portal.submit_request') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </x-card>
</div>
