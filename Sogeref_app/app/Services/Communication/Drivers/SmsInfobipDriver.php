<?php

namespace App\Services\Communication\Drivers;

use App\Services\Communication\SendResult;

class SmsInfobipDriver extends HttpDriver
{
    protected function provider(): string
    {
        return 'infobip';
    }

    protected function dispatch(array $message): SendResult
    {
        $base = rtrim($this->config['base_url'], '/');
        if (! str_starts_with($base, 'http')) {
            $base = 'https://'.$base;
        }
        $response = $this->http()->withHeaders(['Authorization' => 'App '.$this->config['api_key']])
            ->post($base.'/sms/2/text/advanced', ['messages' => [[
                'destinations' => [['to' => $this->digits($message['to'])]],
                'from' => $message['from'] ?: null,
                'text' => $message['body'],
            ]]]);

        return $response->successful() ? SendResult::sent($this->provider(), $response->json('messages.0.messageId')) : $this->failure($response);
    }
}
