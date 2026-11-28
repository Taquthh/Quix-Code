<x-layouts::portal :title="__('help.title')">
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-2">
            <a href="{{ route('portal.overview') }}" wire:navigate class="inline-flex min-h-11 items-center text-sm text-slate-600 hover:text-growth dark:text-slate-400">← {{ __('help.back') }}</a>
            <flux:heading size="xl">{{ __('help.title') }}</flux:heading>
            <flux:text>{{ __('help.subtitle') }}</flux:text>
        </div>

        <x-card :title="__('help.faq_heading')" data-help="faq">
            <div class="flex flex-col divide-y divide-slate-200 dark:divide-slate-800">
                @foreach (__('help.faq') as $index => $item)
                    <details class="group py-1" data-faq-item>
                        <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 text-sm font-medium text-slate-900 dark:text-slate-50">
                            <span class="min-w-0 break-words">{{ $item['question'] }}</span>
                            <span aria-hidden="true" class="shrink-0 text-slate-500 transition group-open:rotate-180">▾</span>
                        </summary>
                        <p class="pb-3 text-sm text-slate-700 dark:text-slate-300">{{ str_replace(':rounds', (string) config('quix.free_revision_rounds'), $item['answer']) }}</p>
                    </details>
                @endforeach
            </div>
        </x-card>

        <x-card :title="__('help.support_heading')" data-help="support">
            <div class="flex flex-col gap-4">
                <p class="text-sm text-slate-700 dark:text-slate-300">{{ __('help.support_intro') }}</p>

                @forelse ($projects as $project)
                    <flux:button class="min-h-11 w-full justify-start sm:w-auto" :href="route('portal.projects.negotiation', $project->id)" wire:navigate data-support-chat="{{ $project->id }}">
                        {{ __('help.chat_project', ['project' => $project->title]) }}
                    </flux:button>
                @empty
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('help.no_active_project') }}</p>
                @endforelse

                @if (filled($supportEmail) || filled($supportWhatsapp))
                    <div class="flex flex-col gap-2 border-t border-slate-200 pt-4 text-sm dark:border-slate-800">
                        <p class="text-slate-600 dark:text-slate-400">{{ __('help.other_channels') }}</p>
                        @if (filled($supportEmail))
                            <a class="inline-flex min-h-11 items-center break-all font-medium text-growth hover:underline" href="mailto:{{ $supportEmail }}" data-support="email">{{ $supportEmail }}</a>
                        @endif
                        @if (filled($supportWhatsapp))
                            <a class="inline-flex min-h-11 items-center font-medium text-growth hover:underline" href="https://wa.me/{{ preg_replace('/\D+/', '', (string) $supportWhatsapp) }}" target="_blank" rel="noopener noreferrer" data-support="whatsapp">WhatsApp</a>
                        @endif
                    </div>
                @endif
            </div>
        </x-card>
    </div>
</x-layouts::portal>
