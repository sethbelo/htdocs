<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMove;
use App\Models\Warehouse;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Point d'entrée unique des mouvements de stock : met à jour les niveaux
 * par entrepôt, le stock total de l'article et trace le mouvement.
 */
class StockService
{
    public function receive(Product $product, Warehouse $to, float $qty, string $type = 'in', ?Model $source = null, ?string $reference = null, ?float $unitCost = null, ?string $notes = null): StockMove
    {
        return $this->move($product, null, $to, $qty, $type, $source, $reference, $unitCost, $notes);
    }

    public function issue(Product $product, Warehouse $from, float $qty, string $type = 'out', ?Model $source = null, ?string $reference = null, ?string $notes = null, bool $allowNegative = false): StockMove
    {
        return $this->move($product, $from, null, $qty, $type, $source, $reference, null, $notes, $allowNegative);
    }

    public function transfer(Product $product, Warehouse $from, Warehouse $to, float $qty, ?Model $source = null, ?string $reference = null, ?string $notes = null): StockMove
    {
        if ($from->is($to)) {
            throw new DomainException('Les entrepôts source et destination doivent être différents.');
        }

        return $this->move($product, $from, $to, $qty, 'transfer', $source, $reference, null, $notes);
    }

    /** Ajuste le stock d'un entrepôt à une quantité comptée. */
    public function adjustTo(Product $product, Warehouse $warehouse, float $counted, ?Model $source = null, ?string $reference = null): ?StockMove
    {
        $current = $this->available($product, $warehouse);
        $delta = round($counted - $current, 4);
        if ($delta == 0.0) {
            return null;
        }

        return $delta > 0
            ? $this->move($product, null, $warehouse, $delta, 'adjust', $source, $reference, null, 'Ajustement inventaire (+)')
            : $this->move($product, $warehouse, null, abs($delta), 'adjust', $source, $reference, null, 'Ajustement inventaire (−)', true);
    }

    public function available(Product $product, Warehouse $warehouse): float
    {
        return (float) StockLevel::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('quantity');
    }

    private function move(Product $product, ?Warehouse $from, ?Warehouse $to, float $qty, string $type, ?Model $source, ?string $reference, ?float $unitCost, ?string $notes, bool $allowNegative = false): StockMove
    {
        if ($qty <= 0) {
            throw new DomainException('La quantité doit être positive.');
        }

        return DB::transaction(function () use ($product, $from, $to, $qty, $type, $source, $reference, $unitCost, $notes, $allowNegative) {
            if ($from) {
                $level = StockLevel::firstOrCreate(['product_id' => $product->id, 'warehouse_id' => $from->id]);
                $level = StockLevel::whereKey($level->id)->lockForUpdate()->first();
                if (! $allowNegative && (float) $level->quantity + 1e-6 < $qty) {
                    throw new DomainException(sprintf('Stock insuffisant pour « %s » dans %s : %s disponible(s), %s demandé(s).', $product->name, $from->name, qty($level->quantity), qty($qty)));
                }
                $level->update(['quantity' => round((float) $level->quantity - $qty, 4)]);
            }
            if ($to) {
                $level = StockLevel::firstOrCreate(['product_id' => $product->id, 'warehouse_id' => $to->id]);
                $level->update(['quantity' => round((float) $level->quantity + $qty, 4)]);
            }

            // Stock total (toutes zones) conservé sur l'article pour les listes et alertes.
            $product->update(['stock_quantity' => StockLevel::where('product_id', $product->id)->sum('quantity')]);

            return StockMove::create([
                'company_id' => $product->company_id,
                'product_id' => $product->id,
                'from_warehouse_id' => $from?->id,
                'to_warehouse_id' => $to?->id,
                'type' => $type,
                'quantity' => $qty,
                'unit_cost' => $unitCost ?? (float) $product->purchase_price,
                'reference' => $reference,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'user_id' => auth()->id(),
                'notes' => $notes,
                'moved_at' => now(),
            ]);
        });
    }
}
