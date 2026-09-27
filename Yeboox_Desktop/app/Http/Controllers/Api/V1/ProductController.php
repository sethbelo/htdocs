<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'in:product,service,combo'],
            'active' => ['nullable', 'boolean'],
            'updated_after' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $products = Product::query()->with('unitOfMeasure')
            ->when($data['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")))
            ->when($data['category'] ?? null, fn (Builder $query, string $category) => $query->where('category', $category))
            ->when($data['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when(array_key_exists('active', $data), fn (Builder $query) => $query->where('is_active', (bool) $data['active']), fn (Builder $query) => $query->where('is_active', true))
            ->when($data['updated_after'] ?? null, fn (Builder $query, string $date) => $query->where('updated_at', '>', $date))
            ->orderBy('id')
            ->cursorPaginate($data['per_page'] ?? 50);

        return ProductResource::collection($products);
    }

    public function show(Product $product): ProductResource
    {
        abort_unless($product->is_active || request()->user()->can('manage inventory'), 404);

        return new ProductResource($product->load('unitOfMeasure'));
    }
}
