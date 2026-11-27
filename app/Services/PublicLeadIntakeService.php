<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\Currency;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use App\Models\UserConsent;
use App\Notifications\LeadWelcomeNotification;
use App\Support\ActivityLogger;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Pemrosesan pengajuan proyek baru dari etalase publik (T6-02, T6-04, T6-07, T6-08).
 * Membuat akun Klien berstatus Lead tanpa mengirim password mentah, menyimpan UTM,
 * mencatat bukti persetujuan UU PDP, dan menolak duplikat prospek yang masih terbuka.
 */
class PublicLeadIntakeService
{
    private const OPEN_LEAD_STATUSES = [
        ProjectStatus::Lead->value,
        ProjectStatus::Quoted->value,
        ProjectStatus::Negotiating->value,
    ];

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     whatsapp: string,
     *     title: string,
     *     service_type: string,
     *     description: string,
     *     currency: Currency,
     *     budget_reference: string|null,
     *     locale: string,
     *     utm: array<string, string>,
     *     ip: string|null
     * }  $input
     */
    public function submit(array $input): Project
    {
        $email = Str::lower(trim($input['email']));
        $normalizedPhone = $this->normalizeWhatsapp($input['whatsapp']);

        $this->guardDuplicateLead($email, $normalizedPhone);

        /** @var array{project: Project, user: User, is_new_user: bool} $created */
        $created = DB::transaction(function () use ($input, $email, $normalizedPhone): array {
            $user = User::query()->where('email', $email)->first();
            $isNewUser = false;

            if ($user === null) {
                $user = new User([
                    'name' => trim($input['name']),
                    'email' => $email,
                    'whatsapp' => $normalizedPhone,
                    'locale' => $input['locale'],
                    'password' => Str::random(48),
                ]);
                $user->role = UserRole::Client;
                $user->preferred_currency = $input['currency'];
                $user->is_active = true;
                $user->save();
                $isNewUser = true;
            } elseif (blank($user->whatsapp) && $normalizedPhone !== null) {
                $user->forceFill(['whatsapp' => $normalizedPhone])->save();
            }

            $budgetMinor = filled($input['budget_reference'])
                ? Money::fromMajor((string) $input['budget_reference'], $input['currency'])->amount
                : null;

            $description = trim($input['description']);
            if ($input['service_type'] !== '') {
                $description = '['.__('public.services.items.'.$input['service_type'].'.title')."]\n\n".$description;
            }

            $project = new Project([
                'client_id' => $user->id,
                'title' => trim($input['title']),
                'description' => $description,
                'lead_source' => 'website',
                'currency' => $input['currency'],
                'budget_reference' => $budgetMinor,
            ]);
            $project->utm = $input['utm'];
            $project->save();

            UserConsent::query()->create([
                'user_id' => $user->id,
                'type' => 'privacy_policy',
                'version' => (string) config('quix.legal.privacy_version', '2026.1'),
                'ip' => $input['ip'],
                'granted_at' => now(),
            ]);

            ActivityLogger::log(ActivityAction::ProjectCreated, $project, [
                'title' => $project->title,
                'source' => 'public_intake',
            ], $user);

            return [
                'project' => $project,
                'user' => $user,
                'is_new_user' => $isNewUser,
            ];
        });

        if ($created['is_new_user']) {
            $token = Password::broker()->createToken($created['user']);
            $setupUrl = route('password.reset', [
                'token' => $token,
                'email' => $created['user']->email,
            ]);

            $created['user']->notify(
                (new LeadWelcomeNotification($created['project'], $setupUrl))->locale($input['locale'])
            );
        }

        return $created['project'];
    }

    private function guardDuplicateLead(string $email, ?string $normalizedPhone): void
    {
        $existingClientIds = User::query()
            ->where('role', UserRole::Client->value)
            ->where(function ($query) use ($email, $normalizedPhone): void {
                $query->where('email', $email);

                if ($normalizedPhone !== null) {
                    $query->orWhere('whatsapp', $normalizedPhone);
                }
            })
            ->pluck('id');

        if ($existingClientIds->isEmpty()) {
            return;
        }

        $hasOpenLead = Project::query()
            ->whereIn('client_id', $existingClientIds)
            ->whereIn('status', self::OPEN_LEAD_STATUSES)
            ->exists();

        if ($hasOpenLead) {
            throw ValidationException::withMessages([
                'email' => __('public.intake.errors.duplicate_lead'),
            ]);
        }
    }

    public function normalizeWhatsapp(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        return $digits;
    }
}
