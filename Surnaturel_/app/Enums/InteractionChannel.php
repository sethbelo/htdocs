<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InteractionChannel: string
{
    use HasOptions;

    case Call = 'call';
    case WhatsApp = 'whatsapp';
    case Sms = 'sms';
    case Email = 'email';
    case Visit = 'visit';
    case Note = 'note';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Appel',
            self::WhatsApp => 'WhatsApp',
            self::Sms => 'SMS',
            self::Email => 'E-mail',
            self::Visit => 'Visite / rendez-vous',
            self::Note => 'Note interne',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Call => 'phone',
            self::WhatsApp => 'chat-bubble-left-right',
            self::Sms => 'chat-bubble-bottom-center-text',
            self::Email => 'envelope',
            self::Visit => 'map-pin',
            self::Note => 'pencil-square',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Call => 'sky',
            self::WhatsApp => 'emerald',
            self::Sms => 'indigo',
            self::Email => 'purple',
            self::Visit => 'amber',
            self::Note => 'zinc',
        };
    }
}
