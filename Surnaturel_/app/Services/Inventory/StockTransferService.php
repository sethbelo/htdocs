<?php

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Enums\StockTransferStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Support\NumberGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Transferts entre entrepôts : Demandé → En transit (sortie source) → Réceptionné (entrée destination),
 * avec écarts de réception et traçabilité de chaque intervenant.
 */
class StockTransferService
{
    public function __construct(private StockService $stock, private NumberGenerator $numbers) {}

    /**
     * @param  list<array{product_id: int|string, quantity: float|int|string}>  $lines
     */
    public function request(Warehouse $from, Warehouse $to, array $lines, User $user, ?string $notes = null): StockTransfer
    {
        if ($from->is($to)) {
            throw new BusinessRuleException('Les entrepôts source et destination doivent être différents.');
        }

        if (! $user->canAccessSite($from->site_id) && ! $user->canAccessSite($to->site_id)) {
            throw new BusinessRuleException('Aucun des deux entrepôts n’appartient à vos sièges.');
        }

        $lines = array_values(array_filter($lines, fn (array $line): bool => ! empty($line['product_id']) && (float) ($line['quantity'] ?? 0) > 0));

        if ($lines === []) {
            throw new BusinessRuleException('Ajoutez au moins un article.');
        }

        return DB::transaction(function () use ($from, $to, $lines, $user, $notes): StockTransfer {
            $transfer = StockTransfer::query()->create([
                'number' => $this->numbers->next(NumberGenerator::StockTransfer),
                'from_warehouse_id' => $from->id,
                'to_warehouse_id' => $to->id,
                'status' => StockTransferStatus::Requested,
                'requested_by' => $user->id,
                'requested_at' => now(),
                'notes' => $notes,
            ]);

            foreach ($lines as $line) {
                $transfer->items()->create(['product_id' => (int) $line['product_id'], 'quantity_requested' => (float) $line['quantity']]);
            }

            return $transfer;
        });
    }

    /**
     * Expédition : sortie de l'entrepôt source.
     *
     * @param  array<int|string, float|int|string>  $shipped  quantités expédiées par ligne (id => quantité)
     */
    public function ship(StockTransfer $transfer, User $user, array $shipped = [], ?string $carrierName = null): StockTransfer
    {
        $this->ensureStatus($transfer, StockTransferStatus::Requested);
        $transfer->loadMissing('from', 'to', 'items.product');

        if (! $user->canAccessSite($transfer->from->site_id)) {
            throw new BusinessRuleException('L’expédition doit être faite par l’entrepôt source.');
        }

        return DB::transaction(function () use ($transfer, $user, $shipped, $carrierName): StockTransfer {
            foreach ($transfer->items as $item) {
                $quantity = (float) ($shipped[$item->id] ?? $item->quantity_requested);
                $item->update(['quantity_shipped' => $quantity]);

                if ($quantity > 0) {
                    $this->stock->move($item->product, $transfer->from, StockMovementType::TransferOut, $quantity, $transfer, "Transfert {$transfer->number} vers {$transfer->to->name}", $user, relatedWarehouse: $transfer->to);
                }
            }

            $transfer->update(['status' => StockTransferStatus::InTransit, 'shipped_by' => $user->id, 'shipped_at' => now(), 'carrier_name' => $carrierName]);

            return $transfer;
        });
    }

    /**
     * Réception : entrée dans l'entrepôt destination (les écarts sont conservés sur chaque ligne).
     *
     * @param  array<int|string, float|int|string>  $received
     */
    public function receive(StockTransfer $transfer, User $user, array $received = [], ?string $notes = null): StockTransfer
    {
        $this->ensureStatus($transfer, StockTransferStatus::InTransit);
        $transfer->loadMissing('from', 'to', 'items.product');

        if (! $user->canAccessSite($transfer->to->site_id)) {
            throw new BusinessRuleException('La réception doit être faite par l’entrepôt destinataire.');
        }

        return DB::transaction(function () use ($transfer, $user, $received, $notes): StockTransfer {
            foreach ($transfer->items as $item) {
                $quantity = (float) ($received[$item->id] ?? $item->quantity_shipped);

                if ($quantity > (float) $item->quantity_shipped + 0.0001) {
                    throw new BusinessRuleException("Quantité reçue supérieure à la quantité expédiée pour « {$item->product->name} ».");
                }

                $item->update(['quantity_received' => $quantity]);

                if ($quantity > 0) {
                    $this->stock->move($item->product, $transfer->to, StockMovementType::TransferIn, $quantity, $transfer, "Transfert {$transfer->number} depuis {$transfer->from->name}", $user, relatedWarehouse: $transfer->from);
                }
            }

            $transfer->update(['status' => StockTransferStatus::Received, 'received_by' => $user->id, 'received_at' => now(), 'reception_notes' => $notes]);

            return $transfer;
        });
    }

    public function cancel(StockTransfer $transfer, User $user, string $reason): StockTransfer
    {
        $this->ensureStatus($transfer, StockTransferStatus::Requested);

        if (blank($reason)) {
            throw new BusinessRuleException('Le motif d’annulation est obligatoire.');
        }

        $transfer->update(['status' => StockTransferStatus::Cancelled, 'cancelled_by' => $user->id, 'cancellation_reason' => $reason]);

        return $transfer;
    }

    /**
     * Transfert immédiat (même responsable des deux côtés) : demande, expédition et réception en une fois.
     *
     * @param  list<array{product_id: int|string, quantity: float|int|string}>  $lines
     */
    public function transferNow(Warehouse $from, Warehouse $to, array $lines, User $user, ?string $carrierName = null, ?string $notes = null): StockTransfer
    {
        return DB::transaction(function () use ($from, $to, $lines, $user, $carrierName, $notes): StockTransfer {
            $transfer = $this->request($from, $to, $lines, $user, $notes);
            $this->ship($transfer, $user, [], $carrierName);

            return $this->receive($transfer->fresh(), $user, [], $notes);
        });
    }

    private function ensureStatus(StockTransfer $transfer, StockTransferStatus $expected): void
    {
        if ($transfer->status !== $expected) {
            throw new BusinessRuleException("Action impossible : le transfert est « {$transfer->status->label()} ».");
        }
    }
}
