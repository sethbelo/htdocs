<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        return app(\App\Http\Controllers\Api\V1\ProductController::class)->index($request);
    }

    public function store(Request $request, TenantContext $tenant)
    {
        $product = Product::create($request->validate($this->rules($tenant)));

        return (new ProductResource($product->load('unitOfMeasure')))
            ->additional(['message' => 'Produit créé.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load('unitOfMeasure'));
    }

    public function update(Request $request, Product $product, TenantContext $tenant): ProductResource
    {
        $product->update($request->validate($this->rules($tenant, $product)));

        return new ProductResource($product->refresh()->load('unitOfMeasure'));
    }

    public function destroy(Product $product)
    {
        if ($product->saleItems()->exists() || $product->stockMovements()->exists()) {
            $product->update(['is_active' => false]);

            return response()->json(['message' => 'Produit désactivé car il possède un historique.']);
        }

        $product->delete();

        return response()->json(['message' => 'Produit supprimé.']);
    }

    private function rules(TenantContext $tenant, ?Product $product = null): array
    {
        $required = $product ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:255'],
            'sku' => [$required, 'string', 'max:100', Rule::unique('products')->where('point_of_sale_id', $tenant->pointOfSaleId())->ignore($product)],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => [$required, 'string', 'max:120'],
            'unit_of_measure_id' => ['nullable', 'integer', Rule::exists('unit_of_measures', 'id')->where('point_of_sale_id', $tenant->pointOfSaleId())],
            'type' => [$required, Rule::in(['product', 'service', 'combo'])],
            'price' => [$required, 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'sales_tax_rate' => ['nullable', 'numeric', 'between:0,100'],
            'tax_included' => ['nullable', 'boolean'],
            'stock_qty' => ['nullable', 'numeric', 'min:0'],
            'alert_qty' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
