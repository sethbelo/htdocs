<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Canal WhatsApp configurable (API WhatsApp Business ou passerelle SMS/WhatsApp compatible JSON).
 * Désactivé tant que WHATSAPP_ENABLED n'est pas vrai.
 */
class WhatsAppChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! config('erp.whatsapp.enabled') || ! config('erp.whatsapp.endpoint')) {
            return;
        }

        $phone = method_exists($notifiable, 'routeNotificationForWhatsApp') ? $notifiable->routeNotificationForWhatsApp() : ($notifiable->whatsapp ?? $notifiable->phone ?? null);

        if (! $phone || ! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $response = Http::withToken((string) config('erp.whatsapp.token'))
            ->timeout(10)
            ->post((string) config('erp.whatsapp.endpoint'), [
                'to' => preg_replace('/\D/', '', (string) $phone),
                'message' => $notification->toWhatsApp($notifiable),
            ]);

        if ($response->failed()) {
            Log::warning('Envoi WhatsApp échoué', ['status' => $response->status(), 'phone' => $phone]);
        }
    }
}
