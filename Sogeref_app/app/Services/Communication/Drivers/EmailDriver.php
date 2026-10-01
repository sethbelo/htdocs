<?php

namespace App\Services\Communication\Drivers;

use App\Services\Communication\SendResult;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * E-mail : un mailer est construit à la volée depuis les paramètres de la
 * société (SMTP ou journal) — la configuration globale n'est pas modifiée.
 */
class EmailDriver implements ChannelDriver
{
    public function __construct(private array $config) {}

    public function send(array $message): SendResult
    {
        $provider = $this->config['driver'] === 'smtp' ? 'smtp' : 'log';
        try {
            $mailer = Mail::build($this->transportConfig());
            $sent = $mailer->html($message['html'] ?? nl2br(e($message['body'])), function (Message $mail) use ($message) {
                $mail->to($message['to'])
                    ->from($message['from'] ?: config('mail.from.address'), $this->config['from_name'] ?: config('mail.from.name'))
                    ->subject($message['subject'] ?: '(sans objet)');
                if (filled($this->config['reply_to'] ?? null)) {
                    $mail->replyTo($this->config['reply_to']);
                }
                $mail->text($message['text'] ?? strip_tags((string) $message['body']));
            });

            return SendResult::sent($provider, $sent?->getMessageId());
        } catch (Throwable $e) {
            Log::warning('Communication: échec e-mail', ['error' => $e->getMessage()]);

            return SendResult::failed($provider, $e->getMessage());
        }
    }

    private function transportConfig(): array
    {
        if ($this->config['driver'] !== 'smtp') {
            return ['transport' => 'log', 'channel' => config('mail.mailers.log.channel')];
        }
        $encryption = $this->config['encryption'] ?? 'tls';

        return [
            'transport' => 'smtp',
            'scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'host' => $this->config['host'],
            'port' => (int) $this->config['port'],
            'username' => $this->config['username'] ?: null,
            'password' => $this->config['password'] ?: null,
            'timeout' => 15,
            'auto_tls' => $encryption !== 'none',
        ];
    }
}
