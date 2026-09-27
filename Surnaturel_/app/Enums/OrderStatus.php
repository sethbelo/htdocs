<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum OrderStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case AwaitingPayment = 'awaiting_payment';
    case Paid = 'paid';
    case InDesign = 'in_design';
    case ProofPending = 'proof_pending';
    case Validated = 'validated';
    case PrintQueue = 'print_queue';
    case Printing = 'printing';
    case QualityCheck = 'quality_check';
    case Ready = 'ready';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Closed = 'closed';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';
    case Rework = 'rework';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::AwaitingPayment => 'En attente paiement',
            self::Paid => 'Payée / Acompte',
            self::InDesign => 'Conception',
            self::ProofPending => 'BAT',
            self::Validated => 'Validée',
            self::PrintQueue => 'File impression',
            self::Printing => 'Impression',
            self::QualityCheck => 'Contrôle qualité',
            self::Ready => 'Prête',
            self::OutForDelivery => 'Livraison',
            self::Delivered => 'Livrée',
            self::Closed => 'Clôturée',
            self::Suspended => 'Suspendue',
            self::Cancelled => 'Annulée',
            self::Rework => 'À refaire',
            self::Returned => 'Retournée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft, self::Closed => 'zinc',
            self::AwaitingPayment, self::Suspended => 'amber',
            self::Paid, self::Validated => 'lime',
            self::InDesign, self::ProofPending => 'purple',
            self::PrintQueue, self::Printing, self::QualityCheck => 'indigo',
            self::Ready, self::Delivered => 'emerald',
            self::OutForDelivery => 'sky',
            self::Cancelled, self::Rework, self::Returned => 'red',
        };
    }

    /**
     * Transitions autorisées depuis ce statut (hors reprise après suspension, gérée par le workflow).
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::AwaitingPayment, self::Cancelled],
            self::AwaitingPayment => [self::Paid, self::Suspended, self::Cancelled],
            self::Paid => [self::InDesign, self::PrintQueue, self::Suspended, self::Cancelled],
            self::InDesign => [self::ProofPending, self::Suspended, self::Cancelled],
            self::ProofPending => [self::Validated, self::InDesign, self::Suspended, self::Cancelled],
            self::Validated => [self::PrintQueue, self::Suspended],
            self::PrintQueue => [self::Printing, self::Suspended],
            self::Printing => [self::QualityCheck, self::Suspended],
            self::QualityCheck => [self::Ready, self::Rework],
            self::Rework => [self::PrintQueue, self::InDesign],
            self::Ready => [self::OutForDelivery, self::Delivered],
            self::OutForDelivery => [self::Delivered, self::Ready],
            self::Delivered => [self::Closed, self::Returned],
            self::Returned => [self::Rework, self::Closed],
            self::Suspended => [self::Cancelled],
            self::Closed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Closed, self::Cancelled], true);
    }

    /**
     * Étapes du flux nominal, pour l'affichage de la frise de progression.
     *
     * @return list<self>
     */
    public static function mainFlow(): array
    {
        return [
            self::Draft, self::AwaitingPayment, self::Paid, self::InDesign, self::ProofPending,
            self::Validated, self::PrintQueue, self::Printing, self::QualityCheck, self::Ready,
            self::OutForDelivery, self::Delivered, self::Closed,
        ];
    }
}
