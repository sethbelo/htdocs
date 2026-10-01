<?php

namespace App\Services\Communication\Drivers;

use App\Services\Communication\SendResult;

/** Meta WhatsApp Cloud API : POST /{phone_number_id}/messages (texte). */
class WhatsAppMetaDriver extends HttpDriver
{
    public const GRAPH_URL = 'https://graph.facebook.com/v21.0';

    protected function provider(): string
    {
        return 'meta_cloud';
    }

    protected function dispatch(array $message): SendResult
    {
        if (blank($message['phone_number_id'] ?? null)) {
            return SendResult::failed($this->provider(), 'Identifiant « phone_number_id » manquant pour ce numéro.');
        }
        $response = $this->http()->withToken($this->config['access_token'])
            ->post(self::GRAPH_URL.'/'.$message['phone_number_id'].'/messages', [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $this->digits($message['to']),
                'type' => 'text',
                'text' => ['preview_url' => false, 'body' => $message['body']],
            ]);

        return $response->successful()
            ? SendResult::sent($this->provider(), $response->json('messages.0.id'))
            : $this->failure($response);
    }
}
