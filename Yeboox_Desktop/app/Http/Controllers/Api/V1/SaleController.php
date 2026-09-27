<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SaleResource;
use App\Models\Sale;
use App\Services\MobileSaleService;
use App\Support\DisplayCurrency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['updated_after' => ['nullable', 'date'], 'per_page' => ['nullable', 'integer', 'between:1,100']]);
        $sales = Sale::query()->with(['customer', 'user', 'items.product', 'payments.paymentMethod'])
            ->when(! $request->user()->canAny(['manage sales', 'manage reports']), fn (Builder $query) => $query->where('user_id', $request->user()->id))
            ->when($data['updated_after'] ?? null, fn (Builder $query, string $date) => $query->where('updated_at', '>', $date))
            ->orderByDesc('id')->cursorPaginate($data['per_page'] ?? 30);

        return SaleResource::collection($sales);
    }

    public function store(Request $request, MobileSaleService $sales)
    {
        $data = $request->validate([
            'client_reference' => ['required', 'string', 'max:100'],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'fulfillment_type' => ['required', Rule::in(['dine_in', 'takeaway', 'delivery'])],
            'table_name' => [Rule::requiredIf($request->input('fulfillment_type') === 'dine_in'), 'nullable', 'string', 'max:50'],
            'delivery_phone' => [Rule::requiredIf($request->input('fulfillment_type') === 'delivery'), 'nullable', 'string', 'max:40'],
            'delivery_address' => [Rule::requiredIf($request->input('fulfillment_type') === 'delivery'), 'nullable', 'string', 'max:500'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'payments' => ['required', 'array', 'min:1', 'max:10'],
            'payments.*.method' => ['required', Rule::in(['cash', 'bank', 'mobile_money', 'yepay'])],
            'payments.*.currency' => ['required', Rule::in([DisplayCurrency::baseCode()])],
            'payments.*.amount' => ['required', 'numeric', 'gte:0'],
            'payments.*.received' => ['nullable', 'numeric', 'gte:0'],
            'payments.*.provider' => ['nullable', 'string', 'max:100'],
            'payments.*.reference' => ['nullable', 'string', 'max:120'],
        ]);

        $result = $sales->create($request->user(), $data);
        $response = (new SaleResource($result['sale']))->response();

        return $response->setStatusCode($result['created'] ? 201 : 200)
            ->header('X-Idempotent-Replay', $result['created'] ? 'false' : 'true');
    }

    public function show(Request $request, Sale $sale): SaleResource
    {
        abort_unless($sale->user_id === $request->user()->id || $request->user()->canAny(['manage sales', 'manage reports']), 403);

        return new SaleResource($sale->load(['customer', 'user', 'items.product', 'payments.paymentMethod']));
    }
}
