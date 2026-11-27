<?php

namespace App\Support;

/** Hasil render template notifikasi: subjek (email), paragraf, dan versi template yang dipakai (dicatat di log kirim). */
final readonly class RenderedTemplate
{
    /** @param  list<string>  $paragraphs */
    public function __construct(
        public ?string $subject,
        public array $paragraphs,
        public int $version,
    ) {}

    /** Isi sebagai satu teks, paragraf dipisah baris kosong (WhatsApp dan pratinjau). */
    public function text(): string
    {
        return implode("\n\n", $this->paragraphs);
    }
}
