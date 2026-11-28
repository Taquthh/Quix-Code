<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Sanitasi pesan commit (T5-07) dan pembacaan tag progres [progress:X%] (T5-04).
 *
 * Hasilnya hanya teks polos: tag progres dibuang, HTML dibuang, karakter kontrol dibuang,
 * dan panjangnya dibatasi. Tampilan tetap meng-escape lagi lewat Blade.
 */
class CommitMessage
{
    private const TAG = '/\[\s*progress\s*:\s*(\d{1,6})\s*%?\s*\]/iu';

    /** @return array{message: string, progress: int|null, invalid_progress: bool} */
    public static function parse(string $raw): array
    {
        $progress = null;
        $invalid = false;

        if (preg_match_all(self::TAG, $raw, $matches)) {
            foreach ($matches[1] as $value) {
                $number = (int) $value;
                if ($number < 0 || $number > 100) {
                    $invalid = true;

                    continue;
                }
                $progress = $number; // tag valid terakhir yang berlaku
            }
        }

        $text = (string) preg_replace(self::TAG, '', $raw);
        $text = strip_tags($text);
        $text = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        $subject = '';
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim((string) preg_replace('/\s+/u', ' ', $line));
            if ($line !== '') {
                $subject = $line;
                break;
            }
        }

        return [
            'message' => Str::limit($subject, (int) config('quix.webhook.message_limit', 500) - 1, '…'),
            'progress' => $progress,
            'invalid_progress' => $invalid,
        ];
    }
}
