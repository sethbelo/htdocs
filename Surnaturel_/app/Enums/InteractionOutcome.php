<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InteractionOutcome: string
{
    use HasOptions;

    case Reached = 'reached';
    case NoAnswer = 'no_answer';
    case Interested = 'interested';
    case NotInterested = 'not_interested';
    case Callback = 'callback';
    case Converted = 'converted';
    case Complaint = 'complaint';
    case WrongNumber = 'wrong_number';

    public function label(): string
    {
        return match ($this) {
            self::Reached => 'Joint',
            self::NoAnswer => 'Sans réponse',
            self::Interested => 'Intéressé',
            self::NotInterested => 'Pas intéressé',
            self::Callback => 'À rappeler',
            self::Converted => 'Converti (commande / devis)',
            self::Complaint => 'Réclamation',
            self::WrongNumber => 'Mauvais numéro',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Converted, self::Interested => 'emerald',
            self::Reached => 'sky',
            self::Callback => 'amber',
            self::NoAnswer => 'zinc',
            self::NotInterested, self::WrongNumber, self::Complaint => 'red',
        };
    }
}
