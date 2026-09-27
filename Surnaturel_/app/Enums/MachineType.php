<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum MachineType: string
{
    use HasOptions;

    case DigitalPrinter = 'digital_printer';
    case Offset = 'offset';
    case Plotter = 'plotter';
    case Copier = 'copier';
    case Press = 'press';
    case Guillotine = 'guillotine';
    case Laminator = 'laminator';
    case Finishing = 'finishing';

    public function label(): string
    {
        return match ($this) {
            self::DigitalPrinter => 'Imprimante numérique',
            self::Offset => 'Offset',
            self::Plotter => 'Traceur',
            self::Copier => 'Photocopieuse',
            self::Press => 'Presse',
            self::Guillotine => 'Massicot',
            self::Laminator => 'Plastifieuse',
            self::Finishing => 'Machine de finition',
        };
    }
}
