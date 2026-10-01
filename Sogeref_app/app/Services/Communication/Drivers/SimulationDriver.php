<?php

namespace App\Services\Communication\Drivers;

use App\Services\Communication\SendResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** Mode simulation : rien ne quitte le serveur, l'envoi est journalisé et réputé réussi. */
class SimulationDriver implements ChannelDriver
{
    public function __construct(private string $channel = 'sms') {}

    public function send(array $message): SendResult
    {
        Log::info("Communication [simulation] {$this->channel} → {$message['to']}", ['from' => $message['from'] ?? null, 'subject' => $message['subject'] ?? null, 'body' => Str::limit((string) ($message['body'] ?? ''), 200)]);

        return SendResult::sent('simulation', 'sim-'.Str::lower(Str::random(20)));
    }
}
