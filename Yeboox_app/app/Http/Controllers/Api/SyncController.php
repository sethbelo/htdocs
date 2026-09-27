<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderType;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class SyncController extends Controller
{
    public function pull(Request $request): JsonResponse
    {
        $establishmentId = $request->user()->establishment_id;

        $products = Product::query()
            ->where('establishment_id', $establishmentId)
            ->active()
            ->with(['category:id,name,icon,color'])
            ->withExists('stocks')
            ->withSum('stocks', 'quantity')
            ->select([
                'id', 'name', 'selling_price', 'promo_price', 'promo_start', 'promo_end',
                'category_id', 'image_path', 'description', 'is_available', 'type',
            ])
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->effective_price,
                'category_id' => $product->category_id,
                'category_name' => $product->category?->name,
                'category_icon' => $product->category?->icon,
                'category_color' => $product->category?->color,
                'image' => $product->image_url,
                'description' => $product->description,
                'is_available' => $product->is_available,
                'track_stock' => (bool) $product->stocks_exists,
                'current_stock' => (float) ($product->stocks_sum_quantity ?? 0),
            ]);

        $categories = Category::query()
            ->where('establishment_id', $establishmentId)
            ->active()
            ->ordered()
            ->get(['id', 'name', 'icon', 'color', 'sort_order']);

        $paymentMethods = PaymentMethod::query()
            ->where('establishment_id', $establishmentId)
            ->active()
            ->get(['id', 'name', 'code', 'icon']);

        return response()->json([
            'products' => $products,
            'categories' => $categories,
            'payment_methods' => $paymentMethods,
            'synced_at' => now()->toISOString(),
            'establishment_id' => $establishmentId,
        ]);
    }

    public function push(Request $request): JsonResponse
    {
        $user = $request->user();
        $establishmentId = $user->establishment_id;

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => [
                'required',
                'uuid',
                Rule::exists('products', 'id')->where(fn ($query) => $query
                    ->where('establishment_id', $establishmentId)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'payment_method_id' => [
                'required',
                'uuid',
                Rule::exists('payment_methods', 'id')->where(fn ($query) => $query
                    ->where('establishment_id', $establishmentId)
                    ->where('is_active', true)),
            ],
            'local_order_number' => ['required', 'string', 'max:100'],
            'order_type' => ['nullable', Rule::enum(OrderType::class)],
            'created_at' => ['nullable', 'date', 'before_or_equal:now'],
        ]);

        if ($existing = Order::where('offline_reference', $validated['local_order_number'])->first()) {
            return $this->duplicateResponse($existing);
        }

        try {
            $order = DB::transaction(function () use ($validated, $user, $establishmentId) {
                $products = Product::query()
                    ->whereIn('id', collect($validated['items'])->pluck('product_id'))
                    ->with(['stocks' => fn ($query) => $query->lockForUpdate()])
                    ->get()
                    ->keyBy('id');

                $items = collect($validated['items'])->map(function (array $item) use ($products) {
                    $product = $products->get($item['product_id']);

                    return [
                        'product' => $product,
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->effective_price,
                        'total' => $product->effective_price * $item['quantity'],
                    ];
                });

                $total = $items->sum('total');

                $order = Order::create([
                    'establishment_id' => $establishmentId,
                    'branch_id' => $user->branch_id,
                    'user_id' => $user->id,
                    'order_number' => Order::generateNumber($establishmentId),
                    'offline_reference' => $validated['local_order_number'],
                    'type' => $validated['order_type'] ?? OrderType::DineIn->value,
                    'status' => 'completed',
                    'subtotal' => $total,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total' => $total,
                    'notes' => "Commande hors-ligne : {$validated['local_order_number']}",
                    'is_paid' => true,
                ]);

                foreach ($items as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product']->id,
                        'name' => $item['product']->name,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total' => $item['total'],
                    ]);

                    $this->decrementStock($item['product'], $item['quantity']);
                }

                Payment::create([
                    'establishment_id' => $establishmentId,
                    'order_id' => $order->id,
                    'payment_method_id' => $validated['payment_method_id'],
                    'amount' => $total,
                    'status' => 'completed',
                    'processed_by' => $user->id,
                ]);

                return $order;
            }, 3);

            Log::info('Commande hors-ligne synchronisée.', [
                'order_id' => $order->id,
                'offline_reference' => $validated['local_order_number'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order synced successfully',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        } catch (QueryException $exception) {
            if ($existing = Order::where('offline_reference', $validated['local_order_number'])->first()) {
                return $this->duplicateResponse($existing);
            }

            report($exception);

            return response()->json(['success' => false, 'message' => 'Sync failed.'], 500);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['success' => false, 'message' => 'Sync failed.'], 500);
        }
    }

    public function status(Request $request): JsonResponse
    {
        $establishmentId = $request->user()->establishment_id;
        $lastOrder = Order::where('establishment_id', $establishmentId)->latest()->first();

        return response()->json([
            'online' => true,
            'server_time' => now()->toISOString(),
            'last_order_at' => $lastOrder?->created_at?->toISOString(),
            'establishment_id' => $establishmentId,
        ]);
    }

    private function decrementStock(Product $product, int $quantity): void
    {
        $remaining = $quantity;

        foreach ($product->stocks->sortByDesc('quantity') as $stock) {
            if ($remaining <= 0) {
                break;
            }

            $deduction = min($remaining, max(0, (float) $stock->quantity));
            $stock->decrement('quantity', $deduction);
            $remaining -= $deduction;
        }
    }

    private function duplicateResponse(Order $order): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Order already synced',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'duplicate' => true,
        ]);
    }
}
