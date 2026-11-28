<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Support\Facades\DB;

/**
 * Persetujuan (opt-in) WhatsApp yang tercatat di user_consents: versi teks, IP, waktu beri dan cabut.
 * users.whatsapp_opt_in hanya cermin cepat; sumber kebenarannya adalah baris consent yang aktif.
 */
class WhatsappConsent
{
    public const TYPE = 'whatsapp_notifications';

    public static function currentVersion(): string
    {
        return (string) config('quix.whatsapp_consent_version', '1');
    }

    /** Beri persetujuan. Bila sudah ada persetujuan aktif dengan versi sama, tidak membuat baris baru. */
    public function grant(User $user, ?string $ip): UserConsent
    {
        return DB::transaction(function () use ($user, $ip): UserConsent {
            User::query()->lockForUpdate()->findOrFail($user->getKey());

            $active = $this->active($user);

            if ($active !== null && $active->version === self::currentVersion()) {
                $consent = $active;
            } else {
                $this->closeActive($user);

                $consent = UserConsent::create([
                    'user_id' => $user->getKey(),
                    'type' => self::TYPE,
                    'version' => self::currentVersion(),
                    'ip' => $ip,
                    'granted_at' => now(),
                ]);
            }

            $user->forceFill(['whatsapp_opt_in' => true])->save();

            return $consent;
        });
    }

    /** Cabut persetujuan: baris aktif ditutup (riwayat dipertahankan) dan opt-in dimatikan. */
    public function revoke(User $user): void
    {
        DB::transaction(function () use ($user): void {
            User::query()->lockForUpdate()->findOrFail($user->getKey());

            $this->closeActive($user);

            $user->forceFill(['whatsapp_opt_in' => false])->save();
        });
    }

    public function active(User $user): ?UserConsent
    {
        return UserConsent::query()
            ->where('user_id', $user->getKey())
            ->where('type', self::TYPE)
            ->whereNotNull('granted_at')
            ->whereNull('revoked_at')
            ->latest('granted_at')
            ->latest('id')
            ->first();
    }

    private function closeActive(User $user): void
    {
        UserConsent::query()
            ->where('user_id', $user->getKey())
            ->where('type', self::TYPE)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}
