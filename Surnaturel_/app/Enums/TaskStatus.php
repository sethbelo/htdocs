<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TaskStatus: string
{
    use HasOptions;

    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Todo => 'À faire',
            self::InProgress => 'En cours',
            self::Done => 'Terminée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Todo => 'zinc',
            self::InProgress => 'sky',
            self::Done => 'emerald',
        };
    }
}
