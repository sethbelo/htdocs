<?php

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Events\StockLevelChanged;
use App\Exceptions\BusinessRuleException;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Support\NumberGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Point d'entrée unique de toute variation de stock : chaque mouvement est historisé.
 */
class StockService
{
    public function __construct(private NumberGenerator $numbers) {}

    /**
     * @param  float  $quantity  Quantité positive ; pour un ajustement, le signe indique le sens.
     */
    public function move(
        Product $product,
        Warehouse|int $warehouse,
        StockMovementType $type,
        float $quantity,
        ?Model $source = null,
        ?string $reason = null,
        ?User $user = null,
        ?float $unitCost = null,
        Warehouse|int|null $relatedWarehouse = null,
    ): ?StockMovement {
        if (! $product->is_stockable) {
            return null;
        }

        $warehouseId = $warehouse instanceof Warehouse ? $warehouse->id : $warehouse;
        $signedQuantity = $type->direction() === 0 ? $quantity : abs($quantity) * $type->direction();

        if ($signedQuantity == 0.0) {
            throw new BusinessRuleException('La quantité du mouvement doit être non nulle.');
        }

        $movement = DB::transaction(function () use ($product, $warehouseId, $type, $signedQuantity, $source, $reason, $user, $unitCost, $relatedWarehouse): StockMovement {
            StockLevel::query()->insertOrIgnore([
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'quantity' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $level = StockLevel::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->firstOrFail();

            $newBalance = round((float) $level->quantity + $signedQuantity, 3);

            if ($newBalance < 0 && ! Setting::get('inventory.allow_negative_stock', false)) {
                throw new BusinessRuleException(sprintf(
                    'Stock insuffisant pour « %s » : disponible %s, demandé %s.',
                    $product->name,
                    rtrim(rtrim(number_format((float) $level->quantity, 3, '.', ''), '0'), '.'),
                    abs($signedQuantity),
                ));
            }

            $level->update(['quantity' => $newBalance]);

            return StockMovement::query()->create([
                'number' => $this->numbers->next(NumberGenerator::StockMovement),
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'related_warehouse_id' => $relatedWarehouse instanceof Warehouse ? $relatedWarehouse->id : $relatedWarehouse,
                'type' => $type,
                'quantity' => $signedQuantity,
                'balance_after' => $newBalance,
                'unit_cost' => $unitCost ?? (float) $product->cost_price,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'reason' => $reason,
                'user_id' => $user?->id ?? auth()->id(),
                'moved_at' => now(),
            ]);
        });

        StockLevelChanged::dispatch($product, $warehouseId, (float) $movement->balance_after);

        return $movement;
    }

    /**
     * @return array{out: StockMovement|null, in: StockMovement|null}
     */
    public function transfer(Product $product, Warehouse $from, Warehouse $to, float $quantity, ?User $user = null, ?string $reason = null, ?Model $source = null): array
    {
        if ($from->is($to)) {
            throw new BusinessRuleException('Les entrepôts source et destination doivent être différents.');
        }

        return DB::transaction(fn (): array => [
            'out' => $this->move($product, $from, StockMovementType::TransferOut, $quantity, $source, $reason, $user, relatedWarehouse: $to),
            'in' => $this->move($product, $to, StockMovementType::TransferIn, $quantity, $source, $reason, $user, relatedWarehouse: $from),
        ]);
    }

    /**
     * Inventaire physique : aligne le stock sur la quantité comptée.
     */
    public function adjustTo(Product $product, Warehouse $warehouse, float $countedQuantity, ?User $user = null, ?string $reason = null): ?StockMovement
    {
        $difference = round($countedQuantity - $product->stockIn($warehouse), 3);

        if ($difference == 0.0) {
            return null;
        }

        return $this->move($product, $warehouse, StockMovementType::Adjustment, $difference, null, $reason ?? 'Inventaire physique', $user);
    }
}
