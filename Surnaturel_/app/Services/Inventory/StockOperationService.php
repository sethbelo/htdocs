<?php

namespace App\Services\Inventory;

use App\Enums\StockOperationReason;
use App\Exceptions\BusinessRuleException;
use App\Models\Product;
use App\Models\StockOperation;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Support\NumberGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Bons d'entrée et de sortie saisis par les magasiniers.
 * Chaque bon conserve qui l'a enregistré (utilisateur) et qui a physiquement livré ou retiré la marchandise.
 */
class StockOperationService
{
    public function __construct(private StockService $stock, private NumberGenerator $numbers) {}

    /**
     * @param  array{counterparty_type?: string, supplier_id?: int|null, employee_id?: int|null, department_id?: int|null, performed_by_name: string, performed_by_phone?: string|null, reference?: string|null, notes?: string|null, operated_at?: string|null}  $details
     * @param  list<array{product_id: int|string, quantity: float|int|string, unit_cost?: float|int|string|null}>  $lines
     */
    public function record(Warehouse $warehouse, StockOperationReason $reason, array $details, array $lines, User $user): StockOperation
    {
        if (! $user->canAccessSite($warehouse->site_id)) {
            throw new BusinessRuleException('Vous n’êtes pas rattaché au siège de cet entrepôt.');
        }

        if (blank($details['performed_by_name'] ?? null)) {
            throw new BusinessRuleException('Indiquez la personne qui effectue l’opération.');
        }

        $lines = array_values(array_filter($lines, fn (array $line): bool => (float) ($line['quantity'] ?? 0) > 0 && ! empty($line['product_id'])));

        if ($lines === []) {
            throw new BusinessRuleException('Ajoutez au moins un article.');
        }

        $isEntry = $reason->isEntry();

        return DB::transaction(function () use ($warehouse, $reason, $details, $lines, $user, $isEntry): StockOperation {
            $operation = StockOperation::query()->create([
                'number' => $this->numbers->next($isEntry ? NumberGenerator::StockEntry : NumberGenerator::StockExit),
                'type' => $isEntry ? 'entry' : 'exit',
                'reason' => $reason,
                'warehouse_id' => $warehouse->id,
                'counterparty_type' => $details['counterparty_type'] ?? 'other',
                'supplier_id' => $details['supplier_id'] ?? null,
                'employee_id' => $details['employee_id'] ?? null,
                'department_id' => $details['department_id'] ?? null,
                'performed_by_name' => $details['performed_by_name'],
                'performed_by_phone' => $details['performed_by_phone'] ?? null,
                'reference' => $details['reference'] ?? null,
                'notes' => $details['notes'] ?? null,
                'recorded_by' => $user->id,
                'operated_at' => $details['operated_at'] ?? now(),
            ]);

            $total = 0.0;
            $products = Product::query()->whereIn('id', array_column($lines, 'product_id'))->get()->keyBy('id');

            foreach ($lines as $line) {
                $product = $products[$line['product_id']] ?? throw new BusinessRuleException('Article introuvable.');

                if (! $product->is_stockable) {
                    throw new BusinessRuleException("« {$product->name} » n’est pas géré en stock.");
                }

                $unitCost = isset($line['unit_cost']) && $line['unit_cost'] !== '' && $line['unit_cost'] !== null ? (float) $line['unit_cost'] : (float) $product->cost_price;
                $label = ($isEntry ? 'Livré par ' : 'Retiré par ').$details['performed_by_name'];

                $this->stock->move($product, $warehouse, $reason->movementType(), (float) $line['quantity'], $operation, "{$reason->label()} — {$label}", $user, $unitCost);
                $total += $unitCost * (float) $line['quantity'];
            }

            $operation->update(['total_value' => round($total, 2)]);

            return $operation;
        });
    }
}
