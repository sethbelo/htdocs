<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Http\Resources\ProductResource;
use App\Models\Client;
use App\Models\Product;
use App\Services\Support\NumberGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CatalogController extends Controller
{
    public function products(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('products.view');

        return ProductResource::collection(
            Product::query()->with('stockLevels')->where('is_active', true)
                ->search($request->string('q')->toString() ?: null)
                ->when($request->filled('barcode'), fn ($q) => $q->where('barcode', $request->string('barcode')))
                ->orderBy('name')->paginate(min(100, $request->integer('per_page', 50)))
        );
    }

    public function clients(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('clients.view');

        return ClientResource::collection(Client::query()->search($request->string('q')->toString() ?: null)->orderBy('name')->paginate(25));
    }

    public function storeClient(Request $request, NumberGenerator $numbers): JsonResponse
    {
        Gate::authorize('clients.create');

        $data = $request->validate([
            'type' => ['required', 'in:individual,company,administration,ngo,reseller'],
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        $client = Client::query()->create($data + [
            'number' => $numbers->next(NumberGenerator::Client),
            'segment' => $data['type'] === 'individual' ? 'b2c' : 'b2b',
            'sales_rep_id' => $request->user()->id,
            'site_id' => $request->user()->default_site_id,
        ]);

        return ClientResource::make($client)->response()->setStatusCode(201);
    }
}
