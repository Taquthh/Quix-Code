<?php

namespace App\Services\Whatsapp;

use App\Contracts\WhatsappGateway;
use App\Exceptions\WhatsappGatewayException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Driver HTTP generik: POST JSON {to, message, reference} dengan token Bearer ke services.whatsapp.url.
 * Sesuaikan bentuk permintaan dan respons di sini setelah penyedia dipilih (Bagian 10 blueprint).
 */
class HttpWhatsappGateway implements WhatsappGateway
{
    public function send(string $to, string $message, string $reference): ?string
    {
        $url = (string) config('services.whatsapp.url');

        if ($url === '') {
            throw new WhatsappGatewayException('services.whatsapp.url belum diatur.');
        }

        try {
            $response = Http::withToken((string) config('services.whatsapp.token'))
                ->acceptJson()
                ->timeout((int) config('services.whatsapp.timeout', 5))
                ->retry(2, 200, throw: false)
                ->post($url, ['to' => $to, 'message' => $message, 'reference' => $reference]);
        } catch (ConnectionException $e) {
            throw new WhatsappGatewayException('Gateway tidak terjangkau: '.$e->getMessage(), 0, $e);
        }

        if ($response->failed()) {
            throw new WhatsappGatewayException('Gateway menolak pesan (HTTP '.$response->status().').');
        }

        $id = $response->json('id') ?? $response->json('message_id');

        return is_scalar($id) ? (string) $id : null;
    }
}
