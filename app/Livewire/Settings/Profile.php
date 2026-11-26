<?php

namespace App\Livewire\Settings;

use App\Concerns\ProfileValidationRules;
use App\Enums\Currency;
use App\Models\User;
use App\Support\WhatsappConsent;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profile settings')]
class Profile extends Component
{
    use ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    public string $whatsapp = '';

    public bool $whatsappOptIn = false;

    public string $preferredCurrency = 'IDR';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = $this->freshUser();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->whatsapp = (string) $user->whatsapp;
        $this->whatsappOptIn = (bool) $user->whatsapp_opt_in;
        $this->preferredCurrency = $user->preferred_currency->value;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = $this->freshUser();

        if (config('fortify.lowercase_usernames')) {
            $this->email = Str::lower($this->email);
        }

        $isClient = $user->isClient();

        if ($isClient) {
            $this->whatsapp = (string) preg_replace('/[\s().-]+/', '', $this->whatsapp);
        }

        // Semua aturan divalidasi sekaligus agar profil tidak tersimpan sebagian.
        $validated = $this->validate($this->profileRules($user->id) + ($isClient ? $this->clientRules() : []));
        $validated = Arr::only($validated, ['name', 'email']);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($isClient) {
            $this->saveClientPreferences($user);
        }

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Pengguna yang sedang login dimuat ulang utuh dari database: model dari guard bisa saja hanya
     * membawa sebagian kolom (lihat SetLocale), dan membaca kolom yang hilang melempar MissingAttributeException.
     */
    private function freshUser(): User
    {
        return User::query()->findOrFail(Auth::id());
    }

    /** @return array<string, array<int, mixed>> */
    private function clientRules(): array
    {
        return [
            'whatsapp' => [
                Rule::requiredIf($this->whatsappOptIn),
                'nullable',
                'regex:/^\+?[0-9]{8,15}$/',
            ],
            'whatsappOptIn' => ['boolean'],
            'preferredCurrency' => ['required', Rule::enum(Currency::class)],
        ];
    }

    /**
     * Preferensi klien (T4-08): nomor WhatsApp, opt-in tercatat, dan mata uang tampilan.
     * Opt-in dicatat di user_consents (versi, IP, waktu); mencabutnya menutup persetujuan aktif.
     */
    private function saveClientPreferences(User $user): void
    {
        $user->forceFill([
            'whatsapp' => $this->whatsapp === '' ? null : $this->whatsapp,
            'preferred_currency' => Currency::from($this->preferredCurrency),
        ])->save();

        $consent = new WhatsappConsent;

        if ($this->whatsappOptIn) {
            $consent->grant($user, request()->ip());
        } elseif ($user->whatsapp_opt_in || $consent->active($user) !== null) {
            $consent->revoke($user);
        }
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Flux::toast(text: __('A new verification link has been sent to your email address.'));
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        $user = Auth::user();

        return $user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        $user = Auth::user();

        return ! $user instanceof MustVerifyEmail || $user->hasVerifiedEmail();
    }
}
