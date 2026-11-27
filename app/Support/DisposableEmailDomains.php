<?php

namespace App\Support;

/** Daftar domain email sekali-pakai yang ditolak pada form prospek publik (T6-04). */
class DisposableEmailDomains
{
    /** @var list<string> */
    private const BLOCKED = [
        '10minutemail.com',
        'dispostable.com',
        'fakeinbox.com',
        'getnada.com',
        'guerrillamail.com',
        'guerrillamailblock.com',
        'maildrop.cc',
        'mailinator.com',
        'sharklasers.com',
        'temp-mail.org',
        'tempmail.com',
        'throwawaymail.com',
        'trashmail.com',
        'yopmail.com',
    ];

    public static function isDisposable(string $email): bool
    {
        $at = strrpos($email, '@');

        if ($at === false) {
            return false;
        }

        $domain = strtolower(trim(substr($email, $at + 1)));

        if ($domain === '') {
            return false;
        }

        foreach (self::BLOCKED as $blocked) {
            if ($domain === $blocked || str_ends_with($domain, '.'.$blocked)) {
                return true;
            }
        }

        return false;
    }
}
