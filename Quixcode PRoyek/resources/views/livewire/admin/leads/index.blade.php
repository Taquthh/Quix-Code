<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="font-heading text-2xl font-bold text-slate-900 dark:text-slate-50">{{ __('quotations.leads.title') }}</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('quotations.leads.subtitle') }}</p>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.projects.create')" wire:navigate>{{ __('quotations.leads.add') }}</flux:button>
    </div>

    <div class="max-w-sm">
        <flux:select wire:model.live="status" :label="__('quotations.leads.filter_status')">
            <flux:select.option value="">{{ __('quotations.leads.all_statuses') }}</flux:select.option>
            @foreach ($statuses as $pipelineStatus)
                <flux:select.option :value="$pipelineStatus->value">{{ $pipelineStatus->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <section class="rounded-xl border border-slate-200 p-4 dark:border-slate-800" aria-labelledby="utm-summary-title">
        <h2 id="utm-summary-title" class="font-heading text-sm font-semibold text-slate-900 dark:text-slate-50">{{ __('quotations.leads.utm_title') }}</h2>
        <p class="text-xs text-slate-600 dark:text-slate-400">{{ __('quotations.leads.utm_hint') }}</p>
        @if (collect($utmSummary)->contains(fn ($row) => $row['source'] !== '' || $row['campaign'] !== ''))
            <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($utmSummary as $row)
                    <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-900">
                        <dt class="truncate text-slate-700 dark:text-slate-300">
                            @if ($row['source'] === '' && $row['campaign'] === '')
                                {{ __('quotations.leads.utm_direct') }}
                            @else
                                {{ $row['source'] !== '' ? $row['source'] : '—' }}@if ($row['campaign'] !== '') · {{ $row['campaign'] }}@endif
                            @endif
                        </dt>
                        <dd class="font-semibold tabular-nums text-slate-900 dark:text-slate-50">{{ $row['count'] }}</dd>
                    </div>
                @endforeach
            </dl>
        @else
            <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">{{ __('quotations.leads.utm_none') }}</p>
        @endif
    </section>

    <x-table :empty="$leads->isEmpty()" :empty-title="__('quotations.leads.none')" :empty-description="__('quotations.leads.none_hint')">
        <x-slot:head>
            <tr>
                <th>{{ __('projects.columns.project') }}</th>
                <th>{{ __('projects.columns.client') }}</th>
                <th>{{ __('projects.columns.status') }}</th>
                <th>{{ __('quotations.leads.source') }}</th>
                <th>{{ __('quotations.leads.latest') }}</th>
                <th class="text-end">{{ __('quotations.leads.unread') }}</th>
                <th></th>
            </tr>
        </x-slot:head>

        @foreach ($leads as $lead)
            <tr wire:key="lead-{{ $lead->id }}">
                <td class="font-medium text-slate-900 dark:text-slate-50">{{ $lead->title }}</td>
                <td>{{ $lead->client?->name }}</td>
                <td><x-badge-status :status="$lead->status" /></td>
                <td>
                    {{ $lead->lead_source ? __('projects.lead_sources.'.$lead->lead_source) : '—' }}
                    @php($utm = (array) $lead->utm)
                    @if (! empty($utm['utm_source']) || ! empty($utm['utm_campaign']))
                        <div class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">
                            {{ collect([$utm['utm_source'] ?? null, $utm['utm_medium'] ?? null, $utm['utm_campaign'] ?? null])->filter()->implode(' · ') }}
                        </div>
                    @endif
                </td>
                <td>
                    @if ($lead->latestQuotation)
                        <span class="tabular-nums">v{{ $lead->latestQuotation->version }} · {{ $lead->latestQuotation->totalMoney()->format() }}</span>
                        <x-badge-status class="ms-1" :status="$lead->latestQuotation->status" />
                    @else
                        <span class="text-slate-600 dark:text-slate-400">{{ __('quotations.leads.no_quotation') }}</span>
                    @endif
                </td>
                <td class="text-end tabular-nums">{{ $lead->unread_chats_count }}</td>
                <td class="text-end">
                    <flux:button size="sm" :href="route('admin.projects.negotiation', $lead->id)" wire:navigate>{{ __('quotations.open') }}</flux:button>
                </td>
            </tr>
        @endforeach
    </x-table>

    {{ $leads->links() }}
</div>
