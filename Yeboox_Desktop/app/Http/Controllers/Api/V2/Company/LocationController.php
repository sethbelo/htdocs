<?php

namespace App\Http\Controllers\Api\V2\Company;

use App\Http\Controllers\Controller;
use App\Models\Establishment;
use App\Models\PointOfSale;
use App\Models\PosSubscription;
use App\Services\SaasProvisioner;
use App\Support\DisplayCurrency;
use App\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    public function index(TenantContext $tenant): JsonResponse
    {
        return response()->json(['data' => $tenant->company()?->establishments()
            ->with(['pointsOfSale.subscriptions' => fn ($query) => $query->latest('ends_at')])
            ->orderBy('name')
            ->get()]);
    }

    public function storeEstablishment(Request $request, TenantContext $tenant): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'code' => ['required', 'alpha_dash', 'max:50', Rule::unique('establishments')->where('company_id', $tenant->companyId())],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'timezone' => ['nullable', 'timezone'],
        ]);
        $establishment = Establishment::create([
            ...$data,
            'company_id' => $tenant->companyId(),
            'uuid' => (string) Str::uuid(),
            'timezone' => $data['timezone'] ?? $tenant->company()?->timezone,
            'is_active' => true,
        ]);

        return response()->json(['message' => 'Établissement créé.', 'data' => $establishment], 201);
    }

    public function storePointOfSale(
        Request $request,
        Establishment $establishment,
        TenantContext $tenant,
        SaasProvisioner $provisioner,
    ): JsonResponse {
        abort_unless((int) $establishment->company_id === $tenant->companyId(), 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'code' => ['required', 'alpha_dash', 'max:50', Rule::unique('points_of_sale')->where('establishment_id', $establishment->id)],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'default_currency' => ['nullable', Rule::in(DisplayCurrency::SUPPORTED)],
            'trial_days' => ['nullable', 'integer', 'between:1,90'],
        ]);

        $point = DB::transaction(function () use ($data, $establishment, $tenant, $provisioner): PointOfSale {
            $point = PointOfSale::create([
                ...$data,
                'company_id' => $tenant->companyId(),
                'establishment_id' => $establishment->id,
                'uuid' => (string) Str::uuid(),
                'default_currency' => $data['default_currency'] ?? $tenant->company()?->base_currency,
                'timezone' => $establishment->timezone,
                'is_active' => true,
            ]);
            PosSubscription::create([
                'company_id' => $tenant->companyId(),
                'point_of_sale_id' => $point->id,
                'plan_code' => 'professional',
                'status' => 'trial',
                'starts_at' => now(),
                'ends_at' => now()->addDays((int) ($data['trial_days'] ?? 14)),
                'amount' => 0,
                'currency' => 'USD',
                'billing_period' => 'monthly',
                'max_users' => 10,
                'features' => ['pos' => true, 'inventory' => true, 'accounting' => true, 'api' => true],
            ]);
            $tenant->run($tenant->company(), $establishment, $point, fn () => $provisioner->seedPointOfSale($tenant->company()));

            return $point;
        }, 3);

        return response()->json(['message' => 'Point de vente et essai créés.', 'data' => $point->load('subscriptions')], 201);
    }

    public function updatePointOfSale(Request $request, PointOfSale $pointOfSale, TenantContext $tenant): JsonResponse
    {
        abort_unless((int) $pointOfSale->company_id === $tenant->companyId(), 404);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:190'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'default_currency' => ['sometimes', Rule::in(DisplayCurrency::SUPPORTED)],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $pointOfSale->update($data);

        return response()->json(['message' => 'Point de vente mis à jour.', 'data' => $pointOfSale->refresh()]);
    }

    public function subscribe(Request $request, PointOfSale $pointOfSale, TenantContext $tenant): JsonResponse
    {
        abort_unless($request->user()->is_master_admin, 403, 'Seul l’administrateur master peut activer un abonnement.');
        abort_unless((int) $pointOfSale->company_id === $tenant->companyId(), 404);
        $data = $request->validate([
            'plan_code' => ['required', Rule::in(['starter', 'professional', 'enterprise'])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'grace_ends_at' => ['nullable', 'date', 'after_or_equal:ends_at'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(DisplayCurrency::SUPPORTED)],
            'billing_period' => ['required', Rule::in(['monthly', 'quarterly', 'semiannual', 'annual'])],
            'max_users' => ['required', 'integer', 'between:1,10000'],
            'features' => ['nullable', 'array'],
        ]);
        $subscription = PosSubscription::create([
            ...$data,
            'company_id' => $tenant->companyId(),
            'point_of_sale_id' => $pointOfSale->id,
            'status' => 'active',
            'starts_at' => $data['starts_at'] ?? now(),
        ]);

        return response()->json(['message' => 'Abonnement activé.', 'data' => $subscription], 201);
    }
}
