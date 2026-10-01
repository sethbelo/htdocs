<?php

namespace App\Services\Communication\Drivers;

use App\Services\Communication\SendResult;

/**
 * Passerelle HTTP générique : POST JSON {to, from, message} vers base_url,
 * « Authorization: Bearer {api_key} » si une clé est définie. L'identifiant
 * renvoyé est lu dans id | message_id | messageId.
 */
class SmsHttpDriver extends HttpDriver
{
    protected function provider(): string
    {
        return 'http';
    }

    protected function dispatch(array $message): SendResult
    {
        $request = $this->http();
        if (filled($this->config['api_key'] ?? null)) {
            $request = $request->withToken($this->config['api_key']);
        }
        $response = $request->post($this->config['base_url'], ['to' => $message['to'], 'from' => $message['from'], 'message' => $message['body']]);

        return $response->successful()
            ? SendResult::sent($this->provider(), (string) ($response->json('id') ?? $response->json('message_id') ?? $response->json('messageId') ?? '') ?: null)
            : $this->failure($response);
    }
}
