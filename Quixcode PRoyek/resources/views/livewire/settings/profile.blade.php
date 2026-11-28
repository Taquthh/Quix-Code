<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                    </div>
                @endif
            </div>

            @if (auth()->user()->isClient())
                <flux:separator />

                <div class="space-y-4">
                    <flux:heading size="sm">{{ __('settings.client.heading') }}</flux:heading>

                    <flux:select wire:model="preferredCurrency" :label="__('settings.client.currency')" :description="__('settings.client.currency_hint')">
                        @foreach (\App\Enums\Currency::cases() as $currency)
                            <flux:select.option :value="$currency->value">{{ $currency->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="whatsapp" type="tel" inputmode="tel" autocomplete="tel" placeholder="+628123456789" :label="__('settings.client.whatsapp')" />

                    <flux:checkbox wire:model="whatsappOptIn" :label="__('settings.client.whatsapp_opt_in')" :description="__('settings.client.whatsapp_opt_in_hint')" />
                </div>
            @endif

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:settings.delete-user-form />
        @endif
    </x-settings.layout>
</section>
