<?php

namespace App\Services\Whatsapp;

use App\Contracts\WhatsappGateway;
use Illuminate\Support\Facades\Log;

/** Driver pengembangan: menulis pesan ke log, tidak mengirim apa pun. */
class LogWhatsappGateway implements WhatsappGateway
{
    public function send(string $to, string $message, string $reference): ?string
    {
        Log::info('WhatsApp (driver log)', ['to' => $to, 'reference' => $reference, 'message' => $message]);

        return 'log-'.md5($reference);
    }
}
