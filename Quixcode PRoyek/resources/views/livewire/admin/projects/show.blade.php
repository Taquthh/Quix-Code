@php
    $isAdmin = auth()->user()->isAdmin();
    $cancelled = \App\Enums\ProjectStatus::Cancelled;
@endphp

<div class="flex flex-col gap-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ route('admin.projects.index') }}" wire:navigate
               class="text-sm text-slate-600 hover:text-growth dark:text-slate-400">← {{ __('projects.title') }}</a>
            <div class="mt-1 flex flex-wrap items-center gap-3">
                <h1 class="font-heading text-2xl font-bold text-slate-900 dark:text-slate-50">{{ $project->title }}</h1>
                <x-badge-status :status="$project->status" />
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $project->client?->name }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('viewAny', [\App\Models\Quotation::class, $project])
                <flux:button icon="chat-bubble-left-right" :href="route('admin.projects.negotiation', $project->id)" wire:navigate>{{ __('quotations.open') }}</flux:button>
            @endcan
            @can('update', $project)
                <flux:button icon="pencil-square" :href="route('admin.projects.edit', $project->id)" wire:navigate>{{ __('ui.edit') }}</flux:button>
                @can('delete', $project)
                    <flux:button variant="danger" icon="trash" wire:click="deleteProject" wire:confirm="{{ __('projects.show.confirm_delete_project') }}">
                        {{ __('projects.show.delete_project') }}
                    </flux:button>
                @endcan
            @endcan
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-card border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/15 dark:text-emerald-300" role="status">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Kolom utama --}}
        <div class="flex flex-col gap-6 xl:col-span-2">
            <x-card :title="__('projects.show.overview')">
                <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-slate-600 dark:text-slate-400">{{ __('projects.show.client') }}</dt>
                        <dd class="mt-0.5 font-medium text-slate-900 dark:text-slate-50">
                            {{ $project->client?->name }}
                            @if ($isAdmin && $project->client?->email)
                                <span class="block text-xs font-normal text-slate-600 dark:text-slate-400">{{ $project->client->email }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-600 dark:text-slate-400">{{ __('projects.show.repo') }}</dt>
                        <dd class="mt-0.5 font-medium text-slate-900 dark:text-slate-50">
                            @if ($project->github_repo)
                                <a href="https://github.com/{{ $project->github_repo }}" target="_blank" rel="noopener noreferrer"
                                   class="hover:text-growth hover:underline">{{ $project->github_repo }}</a>
                            @else
                                <span class="font-normal text-slate-600 dark:text-slate-400">{{ __('projects.show.no_repo') }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-600 dark:text-slate-400">{{ __('projects.show.created') }}</dt>
                        <dd class="mt-0.5 font-medium text-slate-900 dark:text-slate-50">{{ $project->created_at->translatedFormat('d M Y') }}</dd>
                    </div>

                    @can('viewPrice', $project)
                        <div>
                            <dt class="text-slate-600 dark:text-slate-400">{{ __('projects.show.currency') }}</dt>
                            <dd class="mt-0.5 font-medium text-slate-900 dark:text-slate-50">{{ $project->currency->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-600 dark:text-slate-400">{{ __('projects.show.budget_reference') }}</dt>
                            <dd class="mt-0.5 font-medium tabular-nums text-slate-900 dark:text-slate-50">
                                {{ $project->budgetReferenceMoney()?->format() ?? __('projects.show.not_set') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-600 dark:text-slate-400">{{ __('projects.show.final_price') }}</dt>
                            <dd class="mt-0.5 font-medium tabular-nums text-slate-900 dark:text-slate-50">
                                {{ $project->finalPriceMoney()?->format() ?? __('projects.show.not_set') }}
                            </dd>
                        </div>
                    @endcan
                </dl>

                <p class="mt-5 whitespace-pre-line border-t border-slate-200 pt-4 text-sm text-slate-700 dark:border-slate-800 dark:text-slate-300">{{ $project->description ?: __('projects.show.no_description') }}</p>
            </x-card>

            {{-- Milestone --}}
            <x-card :title="__('projects.show.milestones_card')">
                @if ($project->milestones->isEmpty())
                    <x-empty-state :title="__('projects.show.no_milestones')" :description="__('projects.show.no_milestones_hint')" />
                @else
                    <ul class="-my-2 divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach ($project->milestones as $milestone)
                            <li wire:key="milestone-{{ $milestone->id }}" class="flex flex-wrap items-center justify-between gap-3 py-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-medium text-slate-900 dark:text-slate-50">{{ $milestone->title }}</span>
                                        <x-badge-status :status="$milestone->status" />
                                    </div>
                                    <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">
                                        {{ __('projects.show.ms_target_short') }} {{ $milestone->target_progress }}%
                                        @if ($milestone->due_date)
                                            · {{ __('projects.show.ms_due_short') }} {{ $milestone->due_date->translatedFormat('d M Y') }}
                                        @endif
                                        @if ($milestone->assignee)
                                            · {{ __('projects.show.ms_assignee_short') }} {{ $milestone->assignee->name }}
                                        @endif
                                    </p>
                                    @foreach ($milestone->acceptances as $acceptance)
                                        <p class="mt-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-300" data-acceptance="{{ $milestone->id }}">
                                            ✓ {{ __('projects.show.ms_accepted_by', ['name' => $acceptance->client?->name ?? '-', 'date' => $acceptance->accepted_at->translatedFormat('d M Y H:i')]) }}
                                            @if ($isAdmin && $acceptance->ip) · IP {{ $acceptance->ip }} @endif
                                        </p>
                                    @endforeach
                                </div>

                                <div class="flex flex-wrap items-center gap-1.5">
                                    @if ($movable[$milestone->id] ?? false)
                                        <select wire:change="setMilestoneStatus({{ $milestone->id }}, $event.target.value)"
                                                aria-label="{{ __('projects.show.ms_status') }}"
                                                class="rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                            @foreach ($milestoneStatuses as $ms)
                                                <option value="{{ $ms->value }}" @selected($milestone->status === $ms)>{{ $ms->label() }}</option>
                                            @endforeach
                                        </select>
                                    @endif

                                    @if ($isAdmin)
                                        <flux:button size="sm" variant="ghost" icon="chevron-up" :aria-label="__('projects.show.ms_up')"
                                                     wire:click="moveMilestone({{ $milestone->id }}, 'up')" :disabled="$loop->first" />
                                        <flux:button size="sm" variant="ghost" icon="chevron-down" :aria-label="__('projects.show.ms_down')"
                                                     wire:click="moveMilestone({{ $milestone->id }}, 'down')" :disabled="$loop->last" />
                                        <flux:button size="sm" variant="ghost" icon="pencil-square" :aria-label="__('ui.edit')"
                                                     wire:click="editMilestone({{ $milestone->id }})" />
                                        <flux:button size="sm" variant="ghost" icon="trash" :aria-label="__('ui.delete')"
                                                     wire:click="deleteMilestone({{ $milestone->id }})"
                                                     wire:confirm="{{ __('projects.show.ms_confirm_delete') }}" />
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($isAdmin)
                    <form wire:submit="saveMilestone" class="mt-5 grid gap-4 border-t border-slate-200 pt-5 dark:border-slate-800 sm:grid-cols-2">
                        <h4 class="font-heading text-sm font-semibold text-slate-900 dark:text-slate-50 sm:col-span-2">
                            {{ $editingMilestoneId ? __('projects.show.ms_edit') : __('projects.show.ms_add') }}
                        </h4>

                        <flux:input wire:model="ms_title" :label="__('projects.show.ms_title')" required class="sm:col-span-2" />
                        <flux:input wire:model="ms_target" type="number" min="0" max="100" inputmode="numeric" :label="__('projects.show.ms_target')" required />
                        <flux:input wire:model="ms_due" type="date" :label="__('projects.show.ms_due')" />

                        <flux:select wire:model="ms_assignee" :label="__('projects.show.ms_assignee')" class="sm:col-span-2">
                            <flux:select.option value="">{{ __('projects.show.ms_unassigned') }}</flux:select.option>
                            @foreach ($milestoneAssignees as $member)
                                <flux:select.option :value="(string) $member->id">{{ $member->name }} · {{ $member->role->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="ms_assignee" class="sm:col-span-2" />

                        <div class="flex justify-end gap-2 sm:col-span-2">
                            @if ($editingMilestoneId)
                                <flux:button variant="ghost" wire:click="cancelMilestoneEdit">{{ __('ui.cancel') }}</flux:button>
                            @endif
                            <flux:button variant="primary" type="submit">{{ __('ui.save') }}</flux:button>
                        </div>
                    </form>
                @endif
            </x-card>

            @if ($isAdmin)
                <x-card :title="__('projects.show.activity_card')">
                    @forelse ($activities as $log)
                        <div wire:key="log-{{ $log->id }}" class="flex flex-wrap items-baseline justify-between gap-2 border-b border-slate-200 py-2 text-sm last:border-0 dark:border-slate-800">
                            <div>
                                <span class="font-medium text-slate-900 dark:text-slate-50">{{ \App\Enums\ActivityAction::tryFrom($log->action)?->label() ?? $log->action }}</span>
                                @if (($detail = $descriptions[$log->id] ?? '') !== '')
                                    <span class="text-slate-600 dark:text-slate-400"> · {{ $detail }}</span>
                                @endif
                            </div>
                            <span class="text-xs text-slate-600 dark:text-slate-400">
                                {{ $log->user?->name ?? __('projects.show.system') }} · {{ $log->created_at->diffForHumans() }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('projects.show.no_activity') }}</p>
                    @endforelse
                </x-card>

                <x-card :title="__('projects.notes.title')">
                    @if ($notes->isEmpty())
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('projects.notes.empty') }}</p>
                    @else
                        <ul class="mb-4 divide-y divide-slate-200 dark:divide-slate-800">
                            @foreach ($notes as $note)
                                <li wire:key="project-note-{{ $note->id }}" class="flex items-start justify-between gap-3 py-3">
                                    <div>
                                        <p class="whitespace-pre-line text-sm text-slate-800 dark:text-slate-200">{{ $note->body }}</p>
                                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ $note->author?->name }} · {{ $note->created_at->diffForHumans() }}</p>
                                    </div>
                                    <flux:button size="sm" variant="ghost" icon="trash" :aria-label="__('projects.notes.delete')" wire:click="deleteNote({{ $note->id }})" wire:confirm="{{ __('projects.notes.confirm_delete') }}" />
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <form wire:submit="addNote" class="flex flex-col gap-3 border-t border-slate-200 pt-4 dark:border-slate-800">
                        <flux:textarea wire:model="noteBody" :label="__('projects.notes.new')" rows="3" />
                        <flux:error name="noteBody" />
                        <div class="flex justify-end"><flux:button type="submit">{{ __('projects.notes.add') }}</flux:button></div>
                    </form>
                </x-card>
            @endif
        </div>

        {{-- Kolom samping --}}
        <div class="flex flex-col gap-6">
            <x-card :title="__('projects.show.progress_card')">
                <x-progress-bar :value="$project->progress_percent" size="lg" />

                @can('update', $project)
                    <form wire:submit="updateProgress" class="mt-4 flex flex-col gap-3">
                        <flux:input wire:model="progress" type="number" min="0" max="100" inputmode="numeric" :aria-label="__('projects.fields.progress')" />
                        <flux:error name="progress" />
                        <p class="text-xs text-slate-600 dark:text-slate-400">{{ __('projects.show.progress_hint') }}</p>
                        <flux:button type="submit" variant="primary">{{ __('projects.show.update_progress') }}</flux:button>
                    </form>
                @endcan
            </x-card>

            @if ($isAdmin)
                @if ($project->isAwaitingFullProgressConfirmation())
                    <div role="alert" data-banner="pending-100" class="rounded-card border border-amber-300 bg-amber-50 p-card text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                        <p class="font-semibold">{{ __('projects.webhook.pending_title') }}</p>
                        <p class="mt-1">{{ __('projects.webhook.pending_body') }}</p>
                        <flux:button class="mt-3" variant="primary" wire:click="confirmFullProgress" wire:confirm="{{ __('projects.webhook.confirm_prompt') }}">{{ __('projects.webhook.confirm_button') }}</flux:button>
                    </div>
                @endif

                <x-card :title="__('projects.webhook.card_title')" data-section="github">
                    @if (blank($project->github_repo))
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('projects.webhook.repo_missing') }}</p>
                    @else
                        <dl class="flex flex-col gap-3 text-sm">
                            <div>
                                <dt class="text-xs text-slate-600 dark:text-slate-400">{{ __('projects.webhook.repo') }}</dt>
                                <dd class="break-all font-medium text-slate-900 dark:text-slate-50">{{ $project->github_repo }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-600 dark:text-slate-400">{{ __('projects.webhook.endpoint') }}</dt>
                                <dd class="break-all font-mono text-xs text-slate-900 dark:text-slate-50">{{ $webhookUrl }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-600 dark:text-slate-400">{{ __('projects.webhook.secret') }}</dt>
                                <dd class="text-slate-900 dark:text-slate-50">{{ filled($project->webhook_secret) ? __('projects.webhook.secret_set') : __('projects.webhook.secret_unset') }}</dd>
                            </div>
                        </dl>

                        @if ($revealedSecret)
                            <div class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm dark:border-emerald-900 dark:bg-emerald-950/40" data-secret="revealed">
                                <p class="font-semibold text-emerald-900 dark:text-emerald-200">{{ __('projects.webhook.revealed_title') }}</p>
                                <p class="mt-1 break-all font-mono text-xs text-emerald-900 dark:text-emerald-200">{{ $revealedSecret }}</p>
                                <p class="mt-2 text-xs text-emerald-900 dark:text-emerald-200">{{ __('projects.webhook.revealed_hint') }}</p>
                                <flux:button class="mt-2" size="sm" wire:click="hideWebhookSecret">{{ __('projects.webhook.hide') }}</flux:button>
                            </div>
                        @endif

                        <flux:button class="mt-4" wire:click="rotateWebhookSecret" wire:confirm="{{ __('projects.webhook.rotate_prompt') }}">
                            {{ filled($project->webhook_secret) ? __('projects.webhook.rotate') : __('projects.webhook.generate') }}
                        </flux:button>
                        <p class="mt-2 text-xs text-slate-600 dark:text-slate-400">{{ __('projects.webhook.setup_hint') }}</p>
                    @endif

                    <h4 class="mt-5 text-sm font-semibold text-slate-900 dark:text-slate-50">{{ __('projects.webhook.recent_deliveries') }}</h4>
                    @forelse ($deliveries as $delivery)
                        <div wire:key="delivery-{{ $delivery->id }}" class="mt-1 flex items-start justify-between gap-2">
                            <p class="break-words text-xs text-slate-700 dark:text-slate-300">
                                {{ $delivery->received_at?->translatedFormat('d M H:i') }} · {{ $delivery->event }}
                                @if (str_starts_with($delivery->delivery_id, 'reconcile-')) ({{ __('projects.webhook.reconciled') }}) @endif ·
                                {{ __('projects.webhook.status.'.$delivery->status) }}
                                @if ($delivery->error) · {{ $delivery->error }} @endif
                            </p>
                            @if ($delivery->signature_valid && in_array($delivery->status, ['failed', 'received'], true))
                                <flux:button size="xs" wire:click="replayDelivery({{ $delivery->id }})" wire:confirm="{{ __('projects.webhook.replay_prompt') }}">{{ __('projects.webhook.replay') }}</flux:button>
                            @endif
                        </div>
                    @empty
                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('projects.webhook.no_deliveries') }}</p>
                    @endforelse

                    <h4 class="mt-5 text-sm font-semibold text-slate-900 dark:text-slate-50">{{ __('projects.webhook.recent_commits') }}</h4>
                    @forelse ($adminCommits as $commit)
                        <p wire:key="admin-commit-{{ $commit->id }}" class="mt-1 break-words text-xs text-slate-700 dark:text-slate-300">
                            <span class="font-mono">{{ substr($commit->commit_hash, 0, 7) }}</span>
                            · {{ $commit->commit_message !== '' ? $commit->commit_message : __('portal.timeline_fallback') }}
                            · {{ $commit->author?->name ?? $commit->author_email ?? '-' }}
                        </p>
                    @empty
                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('projects.webhook.no_commits') }}</p>
                    @endforelse
                </x-card>

                <x-card :title="__('projects.show.status_card')">
                    @if ($transitions === [])
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('projects.show.no_transitions') }}</p>
                    @else
                        <p class="mb-3 text-sm text-slate-600 dark:text-slate-400">{{ __('projects.show.move_to') }}</p>
                        <flux:field class="mb-4">
                            <flux:label>{{ __('projects.show.status_reason') }}</flux:label>
                            <flux:textarea wire:model="statusReason" rows="2" />
                            <flux:error name="statusReason" />
                        </flux:field>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($transitions as $next)
                                @if ($next === $cancelled)
                                    <flux:button size="sm" variant="danger" wire:key="to-{{ $next->value }}"
                                                 wire:click="changeStatus('{{ $next->value }}')"
                                                 wire:confirm="{{ __('projects.show.confirm_cancel') }}">{{ $next->label() }}</flux:button>
                                @else
                                    <flux:button size="sm" wire:key="to-{{ $next->value }}"
                                                 wire:click="changeStatus('{{ $next->value }}')">{{ $next->label() }}</flux:button>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </x-card>
            @endif

            <x-card :title="__('projects.show.team_card')">
                @if ($projectAdmins->isEmpty() && $project->staff->isEmpty())
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('projects.show.no_staff') }}</p>
                @else
                    <ul class="divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach ($projectAdmins as $member)
                            <li wire:key="admin-{{ $member->id }}" class="flex items-center justify-between gap-2 py-2 text-sm">
                                <div>
                                    <span class="font-medium text-slate-900 dark:text-slate-50">{{ $member->name }}</span>
                                    <span class="block text-xs text-slate-600 dark:text-slate-400">{{ $member->role->label() }}</span>
                                </div>
                            </li>
                        @endforeach
                        @foreach ($project->staff as $member)
                            <li wire:key="staff-{{ $member->id }}" class="flex items-center justify-between gap-2 py-2 text-sm">
                                <div>
                                    <span class="font-medium text-slate-900 dark:text-slate-50">{{ $member->name }}</span>
                                    @if ($member->pivot->task_role)
                                        <span class="block text-xs text-slate-600 dark:text-slate-400">{{ $member->pivot->task_role }}</span>
                                    @endif
                                </div>
                                @if ($isAdmin)
                                    <flux:button size="sm" variant="ghost" wire:click="unassignStaff({{ $member->id }})"
                                                 wire:confirm="{{ __('projects.show.confirm_unassign') }}">{{ __('projects.show.unassign') }}</flux:button>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($isAdmin)
                    <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-800">
                        @if ($availableStaff->isEmpty())
                            <p class="text-xs text-slate-600 dark:text-slate-400">{{ __('projects.show.no_available_staff') }}</p>
                        @else
                            <form wire:submit="assignStaff" class="flex flex-col gap-3">
                                <flux:select wire:model="staffId" :aria-label="__('projects.fields.staff_id')">
                                    <flux:select.option value="">{{ __('projects.show.select_staff') }}</flux:select.option>
                                    @foreach ($availableStaff as $candidate)
                                        <flux:select.option :value="(string) $candidate->id">{{ $candidate->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="staffId" />
                                <flux:input wire:model="taskRole" :placeholder="__('projects.show.task_role_placeholder')" :aria-label="__('projects.fields.task_role')" />
                                <flux:error name="taskRole" />
                                <flux:button type="submit">{{ __('projects.show.assign') }}</flux:button>
                            </form>
                        @endif
                    </div>
                @endif
            </x-card>
        </div>
    </div>
</div>
