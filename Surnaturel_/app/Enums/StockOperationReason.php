<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Motifs des bons d'entrée et de sortie magasin, et type de mouvement de stock associé.
 */
enum StockOperationReason: string
{
    use HasOptions;

    case SupplierDelivery = 'supplier_delivery';
    case CustomerReturn = 'customer_return';
    case ProductionOutput = 'production_output';
    case DonationReceived = 'donation_received';
    case OtherEntry = 'other_entry';
    case WorkshopConsumption = 'workshop_consumption';
    case InternalUse = 'internal_use';
    case DirectSale = 'direct_sale';
    case Loss = 'loss';
    case Breakage = 'breakage';
    case DonationGiven = 'donation_given';
    case OtherExit = 'other_exit';

    public function label(): string
    {
        return match ($this) {
            self::SupplierDelivery => 'Livraison fournisseur',
            self::CustomerReturn => 'Retour client / interne',
            self::ProductionOutput => 'Produits finis de l’atelier',
            self::DonationReceived => 'Don / dotation reçue',
            self::OtherEntry => 'Autre entrée',
            self::WorkshopConsumption => 'Consommation atelier',
            self::InternalUse => 'Remise à un service / employé',
            self::DirectSale => 'Vente hors caisse',
            self::Loss => 'Perte',
            self::Breakage => 'Casse',
            self::DonationGiven => 'Don / échantillon',
            self::OtherExit => 'Autre sortie',
        };
    }

    public function isEntry(): bool
    {
        return in_array($this, [self::SupplierDelivery, self::CustomerReturn, self::ProductionOutput, self::DonationReceived, self::OtherEntry], true);
    }

    public function movementType(): StockMovementType
    {
        return match ($this) {
            self::CustomerReturn => StockMovementType::Return,
            self::WorkshopConsumption => StockMovementType::ProductionConsumption,
            self::Loss => StockMovementType::Loss,
            self::Breakage => StockMovementType::Breakage,
            self::SupplierDelivery, self::ProductionOutput, self::DonationReceived, self::OtherEntry => StockMovementType::In,
            default => StockMovementType::Out,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function optionsFor(string $type): array
    {
        return collect(self::cases())
            ->filter(fn (self $reason): bool => $type === 'entry' ? $reason->isEntry() : ! $reason->isEntry())
            ->mapWithKeys(fn (self $reason): array => [$reason->value => $reason->label()])
            ->all();
    }
}
