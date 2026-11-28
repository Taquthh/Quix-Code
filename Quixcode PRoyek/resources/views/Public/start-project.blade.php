<div>
    <section class="bg-slate-950 py-16 text-white">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h1 class="font-heading text-3xl font-bold sm:text-4xl">{{ __('public.intake.title') }}</h1>
            <p class="mt-3 text-slate-300">{{ __('public.intake.subtitle') }}</p>
        </div>
    </section>

    <section class="mx-auto max-w-3xl px-4 py-14 sm:px-6">
        @if ($submitted)
            <div class="rounded-xl border border-emerald-300 bg-emerald-50 p-6 text-emerald-950 sm:p-8">
                <h2 class="font-heading text-xl font-bold">{{ __('public.intake.success_title') }}</h2>
                <p class="mt-2 text-sm leading-relaxed">
                    {{ __('public.intake.success_body', ['project' => $submittedProjectTitle, 'email' => $email]) }}
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('public.home', ['locale' => $locale]) }}" class="inline-flex min-h-11 items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">
                        {{ __('public.intake.back_home') }}
                    </a>
                    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center rounded-lg border border-emerald-700 px-4 py-2 text-sm font-semibold text-emerald-900">
                        {{ __('public.nav.portal_login') }}
                    </a>
                </div>
            </div>
        @else
            <form wire:submit="submit" class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-xs sm:p-8">
                {{-- Honeypot tersembunyi (T6-04) --}}
                <div class="hidden" aria-hidden="true">
                    <label for="website_url">Website</label>
                    <input id="website_url" type="text" wire:model="website_url" tabindex="-1" autocomplete="off" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-800">{{ __('public.intake.fields.name') }}</label>
                        <input id="name" type="text" wire:model="name" class="mt-1.5 min-h-11 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm focus:border-emerald-600 focus:outline-none" />
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-800">{{ __('public.intake.fields.email') }}</label>
                        <input id="email" type="email" wire:model="email" class="mt-1.5 min-h-11 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm focus:border-emerald-600 focus:outline-none" />
                        @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="whatsapp" class="block text-sm font-medium text-slate-800">{{ __('public.intake.fields.whatsapp') }}</label>
                        <input id="whatsapp" type="text" wire:model="whatsapp" placeholder="081234567890" class="mt-1.5 min-h-11 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm focus:border-emerald-600 focus:outline-none" />
                        @error('whatsapp') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="service_type" class="block text-sm font-medium text-slate-800">{{ __('public.intake.fields.service_type') }}</label>
                        <select id="service_type" wire:model="service_type" class="mt-1.5 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm focus:border-emerald-600 focus:outline-none">
                            <option value="web">{{ __('public.services.items.web.title') }}</option>
                            <option value="automation">{{ __('public.services.items.automation.title') }}</option>
                            <option value="portal">{{ __('public.services.items.portal.title') }}</option>
                        </select>
                        @error('service_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="title" class="block text-sm font-medium text-slate-800">{{ __('public.intake.fields.title') }}</label>
                    <input id="title" type="text" wire:model="title" class="mt-1.5 min-h-11 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm focus:border-emerald-600 focus:outline-none" />
                    @error('title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-slate-800">{{ __('public.intake.fields.description') }}</label>
                    <textarea id="description" rows="4" wire:model="description" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:outline-none"></textarea>
                    @error('description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="currency" class="block text-sm font-medium text-slate-800">{{ __('public.intake.fields.currency') }}</label>
                        <select id="currency" wire:model="currency" class="mt-1.5 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm focus:border-emerald-600 focus:outline-none">
                            @foreach ($currencies as $curr)
                                <option value="{{ $curr->value }}">{{ $curr->value }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="budget_reference" class="block text-sm font-medium text-slate-800">{{ __('public.intake.fields.budget_reference') }}</label>
                        <input id="budget_reference" type="text" wire:model="budget_reference" inputmode="decimal" class="mt-1.5 min-h-11 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm tabular-nums focus:border-emerald-600 focus:outline-none" />
                        @error('budget_reference') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                @error('captcha_token')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <label class="flex items-start gap-3 text-sm text-slate-700">
                        <input type="checkbox" wire:model="consent_privacy" class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600" />
                        <span>
                            {{ __('public.intake.consent_prefix') }}
                            <a href="{{ route('public.privacy', ['locale' => $locale]) }}" target="_blank" class="font-semibold text-emerald-700 underline">{{ __('public.nav.privacy') }}</a>
                            &amp;
                            <a href="{{ route('public.terms', ['locale' => $locale]) }}" target="_blank" class="font-semibold text-emerald-700 underline">{{ __('public.nav.terms') }}</a>.
                        </span>
                    </label>
                    @error('consent_privacy') <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-emerald-500 disabled:opacity-60">
                    <span wire:loading.remove>{{ __('public.intake.submit') }}</span>
                    <span wire:loading>{{ __('public.intake.submitting') }}</span>
                </button>
            </form>
        @endif
    </section>
</div>
