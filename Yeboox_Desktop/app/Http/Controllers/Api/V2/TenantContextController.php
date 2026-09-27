<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\PointOfSale;
use App\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class TenantContextController extends Controller
{
    public function show(Request $request, TenantContext $tenant): JsonResponse
    {
        $user = $request->user();
        $points = $user->is_master_admin || $user->is_company_admin
            ? $tenant->company()?->pointsOfSale()->with('establishment')->get() ?? collect()
            : $user->pointsOfSale()->with('establishment')->get();

        return response()->json(['data' => [
            'company' => $tenant->company(),
            'establishment' => $tenant->establishment(),
            'point_of_sale' => $tenant->pointOfSale(),
            'subscription' => $tenant->pointOfSale()?->activeSubscription(),
            'available_points_of_sale' => $points,
        ]]);
    }

    public function switch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'point_of_sale_id' => ['required', 'integer', 'exists:points_of_sale,id'],
        ]);
        $user = $request->user();
        $point = PointOfSale::query()->with(['company', 'establishment'])->findOrFail($data['point_of_sale_id']);

        $allowed = $user->is_master_admin
            || ($user->is_company_admin && (int) $user->company_id === (int) $point->company_id)
            || $user->pointsOfSale()->whereKey($point->id)->exists();
        abort_unless($allowed && $point->is_active && $point->company->status === 'active', 403, 'Accès à ce point de vente interdit.');

        $user->forceFill([
            'company_id' => $point->company_id,
            'current_establishment_id' => $point->establishment_id,
            'current_point_of_sale_id' => $point->id,
        ])->save();

        $token = $user->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->forceFill([
                'company_id' => $point->company_id,
                'point_of_sale_id' => $point->id,
            ])->save();
        } elseif ($request->hasSession()) {
            $request->session()->put([
                'company_id' => $point->company_id,
                'point_of_sale_id' => $point->id,
            ]);
        }

        return response()->json([
            'message' => "Le point de vente {$point->name} est maintenant actif.",
            'data' => [
                'company' => $point->company,
                'establishment' => $point->establishment,
                'point_of_sale' => $point,
                'subscription' => $point->activeSubscription(),
            ],
        ]);
    }
}
