<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderPriority;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Sales\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('orders.view');

        return OrderResource::collection(
            Order::query()->visibleTo($request->user())->with('client')
                ->search($request->string('q')->toString() ?: null)
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->latest('ordered_at')
                ->paginate(min(100, $request->integer('per_page', 25)))
        );
    }

    public function show(string $number): OrderResource
    {
        $order = Order::query()->where('number', $number)->with('client', 'items')->firstOrFail();
        Gate::authorize('view', $order);

        return OrderResource::make($order);
    }

    public function store(Request $request, OrderService $orders): JsonResponse
    {
        Gate::authorize('orders.create');

        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'site_id' => ['required', Rule::in($request->user()->accessibleSiteIds())],
            'due_at' => ['nullable', 'date'],
            'priority' => ['nullable', Rule::enum(OrderPriority::class)],
            'requires_design' => ['boolean'],
            'deposit_rate' => ['nullable', 'numeric', 'between:0,100'],
            'technical_notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'between:0,100'],
            'items.*.format' => ['nullable', 'string', 'max:30'],
            'items.*.paper' => ['nullable', 'string', 'max:255'],
            'items.*.grammage' => ['nullable', 'integer'],
            'items.*.colors' => ['nullable', 'string', 'max:30'],
            'items.*.print_sides' => ['nullable', 'in:recto,recto_verso'],
            'items.*.finishing' => ['nullable', 'string', 'max:255'],
        ]);

        $order = $orders->create($data, $data['items'], $request->user());

        return OrderResource::make($order->load('client', 'items'))->response()->setStatusCode(201);
    }
}
