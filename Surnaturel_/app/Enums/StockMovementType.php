<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum StockMovementType: string
{
    use HasOptions;

    case In = 'in';
    case Out = 'out';
    case Sale = 'sale';
    case ProductionConsumption = 'production_consumption';
    case PurchaseReceipt = 'purchase_receipt';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Return = 'return';
    case Loss = 'loss';
    case Breakage = 'breakage';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Entrée',
            self::Out => 'Sortie',
            self::Sale => 'Vente',
            self::ProductionConsumption => 'Consommation production',
            self::PurchaseReceipt => 'Réception achat',
            self::TransferIn => 'Transfert entrant',
            self::TransferOut => 'Transfert sortant',
            self::Return => 'Retour',
            self::Loss => 'Perte',
            self::Breakage => 'Casse',
            self::Adjustment => 'Ajustement',
        };
    }

    /**
     * Sens du mouvement : +1 augmente le stock, -1 le diminue, 0 = signe fourni par la quantité (ajustement).
     */
    public function direction(): int
    {
        return match ($this) {
            self::In, self::PurchaseReceipt, self::TransferIn, self::Return => 1,
            self::Out, self::Sale, self::ProductionConsumption, self::TransferOut, self::Loss, self::Breakage => -1,
            self::Adjustment => 0,
        };
    }

    public function color(): string
    {
        return match ($this->direction()) {
            1 => 'emerald',
            -1 => 'red',
            default => 'amber',
        };
    }
}
