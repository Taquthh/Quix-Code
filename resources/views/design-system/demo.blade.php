@php
    use App\Enums\InvoiceStatus;
    use App\Enums\ProjectStatus;

    $rows = [
        ['Sistem Kasir Toko Demo', ProjectStatus::Active, 65],
        ['Portal Klien Cafe', ProjectStatus::AwaitingDp, 0],
        ['Aplikasi Inventori', ProjectStatus::Handover, 100],
    ];
    $long = __('ui.demo.long_sample');
@endphp

<x-layouts::app :title="__('ui.demo.title')">
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-heading text-2xl font-bold text-slate-900 dark:text-slate-50">{{ __('ui.demo.title') }}</h1>
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('ui.demo.subtitle') }}</p>
            </div>
            <x-locale-switcher />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <x-card :title="__('ui.demo.badges')">
                <div class="flex flex-wrap gap-2">
                    @foreach (ProjectStatus::cases() as $status)
                        <x-badge-status :status="$status" />
                    @endforeach
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach (InvoiceStatus::cases() as $status)
                        <x-badge-status :status="$status" />
                    @endforeach
                </div>
            </x-card>

            <x-card :title="__('ui.demo.progress')">
                <div class="flex flex-col gap-4">
                    <x-progress-bar :value="25" size="sm" :label="__('ui.demo.small')" />
                    <x-progress-bar :value="65" :label="__('ui.demo.medium')" />
                    <x-progress-bar :value="100" size="lg" :label="__('ui.demo.large')" />
                </div>
            </x-card>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <x-card :title="__('ui.demo.buttons')" data-styleguide="buttons">
                <div class="flex flex-wrap items-center gap-2">
                    <x-button variant="primary">{{ __('ui.demo.variant.primary') }}</x-button>
                    <x-button variant="secondary">{{ __('ui.demo.variant.secondary') }}</x-button>
                    <x-button variant="danger">{{ __('ui.demo.variant.danger') }}</x-button>
                    <x-button variant="ghost">{{ __('ui.demo.variant.ghost') }}</x-button>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <x-button :loading="true">{{ __('ui.demo.states.loading') }}</x-button>
                    <x-button :disabled="true">{{ __('ui.demo.variant.primary') }}</x-button>
                </div>
            </x-card>

            <x-card :title="__('ui.demo.inputs')" data-styleguide="inputs">
                <div class="space-y-4">
                    <x-input name="demo_normal" :label="__('ui.demo.input_label')" :hint="__('ui.demo.input_hint')" />
                    <x-input name="demo_error" :label="__('ui.demo.input_label')" :error="__('ui.demo.input_error')" />
                    <x-input name="demo_loading" :label="__('ui.demo.states.loading')" :loading="true" />
                </div>
            </x-card>
        </div>

        <x-card :title="__('ui.demo.alerts')" data-styleguide="alerts">
            <div class="grid gap-3 md:grid-cols-2">
                <x-alert type="info">{{ __('ui.demo.alert_info') }}</x-alert>
                <x-alert type="success">{{ __('ui.demo.alert_success') }}</x-alert>
                <x-alert type="warning">{{ __('ui.demo.alert_warning') }}</x-alert>
                <x-alert type="danger" :title="__('ui.demo.states.error')">{{ __('ui.demo.alert_danger') }}</x-alert>
            </div>
        </x-card>

        <x-card :title="__('ui.demo.skeleton')" data-styleguide="skeleton">
            <div class="grid gap-4 md:grid-cols-2">
                <x-skeleton :lines="4" />
                <x-skeleton :block="true" />
            </div>
        </x-card>

        {{-- State tabel: normal, kosong, memuat, galat --}}
        <section data-styleguide="table-states" class="space-y-3">
            <h2 class="font-heading text-lg font-semibold text-slate-900 dark:text-slate-50">{{ __('ui.demo.table_states') }}</h2>

            <x-table>
                <x-slot:head>
                    <tr>
                        <th>{{ __('ui.demo.project') }}</th>
                        <th>{{ __('ui.demo.status') }}</th>
                        <th class="min-w-40">{{ __('ui.progress') }}</th>
                    </tr>
                </x-slot:head>

                @foreach ($rows as [$title, $status, $progress])
                    <tr>
                        <td class="font-medium">{{ $title }}</td>
                        <td><x-badge-status :status="$status" /></td>
                        <td><x-progress-bar :value="$progress" size="sm" /></td>
                    </tr>
                @endforeach
            </x-table>

            <div class="grid gap-4 md:grid-cols-3">
                <x-table :empty="true">
                    <x-slot:head><tr><th>{{ __('ui.demo.states.empty') }}</th></tr></x-slot:head>
                </x-table>
                <x-table :loading="true">
                    <x-slot:head><tr><th>{{ __('ui.demo.states.loading') }}</th></tr></x-slot:head>
                </x-table>
                <x-table :error="__('ui.demo.table_error')">
                    <x-slot:head><tr><th>{{ __('ui.demo.states.error') }}</th></tr></x-slot:head>
                </x-table>
            </div>
        </section>

        {{-- Uji teks sangat panjang (3x): tidak boleh ada komponen yang pecah --}}
        <section data-styleguide="long-text" class="space-y-3">
            <h2 class="font-heading text-lg font-semibold text-slate-900 dark:text-slate-50">{{ __('ui.demo.long_text') }}</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <x-card :title="$long"><p class="break-words text-sm text-slate-700 dark:text-slate-300">{{ $long }}</p></x-card>
                <x-card :title="__('ui.demo.badges')">
                    <x-badge-status status="active" :label="$long" />
                    <x-progress-bar class="mt-3" :value="40" :label="$long" />
                </x-card>
                <x-button variant="primary" class="w-full">{{ $long }}</x-button>
                <x-alert type="warning" :title="$long">{{ $long }}</x-alert>
                <x-input name="demo_long" :label="$long" :hint="$long" :error="$long" />
                <x-empty-state :title="$long" :description="$long" />
            </div>
        </section>

        <x-card :title="__('ui.demo.modal')">
            <x-button x-data x-on:click="$dispatch('open-modal', 'demo')">{{ __('ui.demo.open_modal') }}</x-button>
        </x-card>
    </div>

    <x-modal name="demo" :title="__('ui.demo.modal')">
        <p>{{ __('ui.demo.modal_body') }}</p>
        <x-slot:footer>
            <x-button variant="secondary" x-data x-on:click="$dispatch('close-modal', 'demo')">{{ __('ui.close') }}</x-button>
        </x-slot:footer>
    </x-modal>
</x-layouts::app>
