<?php

namespace App\Services\Communication\Drivers;

use App\Services\Communication\SendResult;

/** Twilio WhatsApp : From/To préfixés « whatsapp: ». */
class WhatsAppTwilioDriver extends HttpDriver
{
    protected function provider(): string
    {
        return 'twilio';
    }

    protected function dispatch(array $message): SendResult
    {
        $payload = ['From' => 'whatsapp:'.$message['from'], 'To' => 'whatsapp:'.$message['to'], 'Body' => $message['body']];
        if (! empty($message['status_callback'])) {
            $payload['StatusCallback'] = $message['status_callback'];
        }
        $response = $this->http()->asForm()->withBasicAuth($this->config['account_sid'], $this->config['auth_token'])
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->config['account_sid']}/Messages.json", $payload);

        return $response->successful() ? SendResult::sent($this->provider(), $response->json('sid')) : $this->failure($response);
    }
}
