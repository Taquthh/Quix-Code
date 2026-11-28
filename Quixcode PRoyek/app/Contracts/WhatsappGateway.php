<?php

namespace App\Contracts;

use App\Exceptions\WhatsappGatewayException;

/** Pengirim WhatsApp (T5-10). Penyedia (pihak ketiga atau Business API resmi) cukup mengimplementasikan ini. */
interface WhatsappGateway
{
    /**
     * @param  string  $to  nomor internasional tanpa tanda plus, mis. 628123456789
     * @param  string  $reference  kunci unik pengiriman; penyedia yang mendukungnya memakainya untuk idempotensi
     * @return string|null id pesan dari penyedia
     *
     * @throws WhatsappGatewayException bila pesan tidak terkirim
     */
    public function send(string $to, string $message, string $reference): ?string;
}
