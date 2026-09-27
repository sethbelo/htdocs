<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DesignNoteType: string
{
    use HasOptions;

    case Note = 'note';
    case Idea = 'idea';
    case ClientFeedback = 'client_feedback';
    case Todo = 'todo';
    case Decision = 'decision';

    public function label(): string
    {
        return match ($this) {
            self::Note => 'Note',
            self::Idea => 'Idée / piste créative',
            self::ClientFeedback => 'Retour client',
            self::Todo => 'À faire',
            self::Decision => 'Décision',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Note => 'pencil',
            self::Idea => 'light-bulb',
            self::ClientFeedback => 'chat-bubble-left-ellipsis',
            self::Todo => 'check-circle',
            self::Decision => 'flag',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Note => 'zinc',
            self::Idea => 'amber',
            self::ClientFeedback => 'sky',
            self::Todo => 'indigo',
            self::Decision => 'emerald',
        };
    }
}
