<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StockController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'integer'],
            'type' => ['nullable', Rule::in(['in', 'out', 'adjustment'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);
        $movements = StockMovement::query()->with(['product:id,name,sku', 'user:id,name'])
            ->when($data['product_id'] ?? null, fn ($query, int $id) => $query->where('product_id', $id))
            ->when($data['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when($data['from'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($data['to'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest('id')
            ->paginate($data['per_page'] ?? 30);

        return response()->json($movements);
    }

    public function adjust(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'operation' => ['required', Rule::in(['set', 'increase', 'decrease'])],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $movement = DB::transaction(function () use ($request, $product, $data): StockMovement {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $before = (float) $locked->stock_qty;
            $quantity = (float) $data['quantity'];
            $after = match ($data['operation']) {
                'set' => $quantity,
                'increase' => $before + $quantity,
                'decrease' => $before - $quantity,
            };
            abort_if($after < 0, 422, 'Le stock ne peut pas devenir négatif.');
            $locked->update(['stock_qty' => $after]);

            return StockMovement::create([
                'product_id' => $locked->id,
                'type' => $data['operation'] === 'increase' ? 'in' : ($data['operation'] === 'decrease' ? 'out' : 'adjustment'),
                'reason' => $data['reason'],
                'reference' => $data['reference'] ?? null,
                'quantity' => $quantity,
                'unit_cost' => $data['unit_cost'] ?? $locked->cost,
                'stock_before' => $before,
                'stock_after' => $after,
                'description' => $data['description'] ?? null,
                'user_id' => $request->user()->id,
            ]);
        }, 3);

        return response()->json(['message' => 'Stock mis à jour.', 'data' => $movement->load('product')], 201);
    }
}
