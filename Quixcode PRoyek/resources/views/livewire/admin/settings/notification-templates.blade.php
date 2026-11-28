<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="font-heading text-2xl font-bold text-slate-900 dark:text-slate-50">{{ __('admin.templates.title') }}</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('admin.templates.subtitle') }}</p>
        </div>
        <flux:button :href="route('admin.settings')" wire:navigate>{{ __('admin.templates.back') }}</flux:button>
    </div>

    <x-card :title="__('admin.templates.choose')">
        <div class="grid gap-4 md:grid-cols-3">
            <flux:select wire:model.live="templateKey" :label="__('admin.templates.key')">
                @foreach ($keys as $key)
                    <flux:select.option :value="$key">{{ __('admin.templates.keys.'.$key) }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="templateLocale" :label="__('admin.templates.locale')">
                @foreach ($locales as $code)
                    <flux:select.option :value="$code">{{ __('admin.templates.locales.'.$code) }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="templateChannel" :label="__('admin.templates.channel')">
                @foreach ($channels as $code)
                    <flux:select.option :value="$code">{{ __('admin.templates.channels.'.$code) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <flux:error name="templateKey" />
    </x-card>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-card :title="__('admin.templates.editor')">
            <form wire:submit="save" class="flex flex-col gap-4">
                @if ($templateChannel === 'email')
                    <flux:input wire:model.live.debounce.300ms="subject" :label="__('admin.templates.subject')" maxlength="190" />
                    <flux:error name="subject" />
                @endif

                <flux:textarea wire:model.live.debounce.300ms="body" :label="__('admin.templates.body')" rows="12" />
                <flux:error name="body" />

                <p class="text-xs text-slate-600 dark:text-slate-400">
                    {{ $templateChannel === 'email' ? __('admin.templates.email_hint') : __('admin.templates.whatsapp_hint') }}
                </p>

                <div>
                    <p class="mb-2 text-xs font-medium uppercase text-slate-600 dark:text-slate-400">{{ __('admin.templates.variables') }}</p>
                    <ul class="flex flex-col gap-1 text-sm">
                        @foreach ($this->variables as $variable)
                            <li wire:key="var-{{ $variable }}">
                                <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-800 dark:bg-slate-800 dark:text-slate-200">{{ $this->token($variable) }}</code>
                                <span class="text-slate-600 dark:text-slate-400">{{ __('admin.templates.variable_labels.'.$variable) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="flex flex-wrap gap-2">
                    <flux:button type="submit" variant="primary">{{ __('admin.templates.save') }}</flux:button>
                    <flux:button type="button" wire:click="useDefault">{{ __('admin.templates.use_default') }}</flux:button>
                </div>
            </form>
        </x-card>

        <div class="flex flex-col gap-6">
            <x-card :title="__('admin.templates.preview')">
                <div class="flex flex-col gap-3 text-sm text-slate-900 dark:text-slate-50" data-testid="template-preview">
                    @if ($templateChannel === 'email')
                        <p class="font-semibold">{{ $this->preview->subject }}</p>
                    @endif
                    @forelse ($this->preview->paragraphs as $paragraph)
                        <p class="whitespace-pre-line">{{ $paragraph }}</p>
                    @empty
                        <p class="text-slate-600 dark:text-slate-400">{{ __('admin.templates.preview_empty') }}</p>
                    @endforelse
                </div>
                <p class="mt-3 text-xs text-slate-600 dark:text-slate-400">{{ __('admin.templates.preview_hint') }}</p>
            </x-card>

            <x-card :title="__('admin.templates.history')">
                @if ($this->versions->isEmpty())
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('admin.templates.no_versions') }}</p>
                @else
                    <ul class="divide-y divide-slate-200 text-sm dark:divide-slate-800">
                        @foreach ($this->versions as $version)
                            <li wire:key="version-{{ $version->id }}" class="flex items-center justify-between gap-3 py-2">
                                <span>
                                    <span class="font-medium text-slate-900 dark:text-slate-50">v{{ $version->version }}</span>
                                    @if ($loop->first)
                                        <span class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300">{{ __('admin.templates.active') }}</span>
                                    @endif
                                    <span class="block text-xs text-slate-600 dark:text-slate-400">{{ $version->created_at?->translatedFormat('j F Y H:i') }}</span>
                                </span>
                                <flux:button size="sm" variant="ghost" wire:click="restore({{ $version->version }})">{{ __('admin.templates.load') }}</flux:button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    </div>
</div>
