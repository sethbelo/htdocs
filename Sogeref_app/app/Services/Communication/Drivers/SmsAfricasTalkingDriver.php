<?php

namespace App\Services\Communication\Drivers;

use App\Services\Communication\SendResult;

/** Africa's Talking (compte « sandbox » → environnement de test). */
class SmsAfricasTalkingDriver extends HttpDriver
{
    protected function provider(): string
    {
        return 'africastalking';
    }

    protected function dispatch(array $message): SendResult
    {
        $host = $this->config['username'] === 'sandbox' ? 'https://api.sandbox.africastalking.com' : 'https://api.africastalking.com';
        $payload = ['username' => $this->config['username'], 'to' => $message['to'], 'message' => $message['body']];
        if (filled($message['from'] ?? null)) {
            $payload['from'] = $message['from'];
        }
        $response = $this->http()->asForm()->withHeaders(['apiKey' => $this->config['api_key']])->post($host.'/version1/messaging', $payload);
        if (! $response->successful()) {
            return $this->failure($response);
        }
        $recipient = $response->json('SMSMessageData.Recipients.0');
        if (! $recipient || ! in_array((int) ($recipient['statusCode'] ?? 0), [100, 101, 102], true)) {
            return SendResult::failed($this->provider(), (string) ($recipient['status'] ?? $response->json('SMSMessageData.Message') ?? 'Envoi refusé'));
        }

        return SendResult::sent($this->provider(), $recipient['messageId'] ?? null);
    }
}
