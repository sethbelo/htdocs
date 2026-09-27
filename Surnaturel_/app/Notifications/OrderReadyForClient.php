<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Setting;
use App\Notifications\Channels\WhatsAppChannel;
use App\Services\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Informe le client que sa commande est prête (e-mail et/ou WhatsApp selon ses coordonnées).
 */
class OrderReadyForClient extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return array_values(array_filter([
            $notifiable->email ? 'mail' : null,
            ($notifiable->whatsapp || $notifiable->phone) && config('erp.whatsapp.enabled') ? WhatsAppChannel::class : null,
        ]));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Votre commande {$this->order->number} est prête")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre commande {$this->order->number} est prête.")
            ->when($this->order->balanceDue() > 0, fn (MailMessage $mail) => $mail->line('Reste à régler : '.Money::format($this->order->balanceDue())))
            ->salutation(Setting::get('company.name'));
    }

    public function toWhatsApp(object $notifiable): string
    {
        $balance = $this->order->balanceDue() > 0 ? ' Reste à régler : '.Money::format($this->order->balanceDue()).'.' : '';

        return 'Bonjour '.$notifiable->name.', votre commande '.$this->order->number.' est prête.'.$balance.' — '.Setting::get('company.name');
    }
}
