<?php

namespace App\Http\Controllers;

use App\Models\PointOfSale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Change le PDV actif de la session Web après vérification d'appartenance. */
class PointOfSalePreferenceController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'point_of_sale_id' => ['required', 'integer', 'exists:points_of_sale,id'],
        ]);
        $user = $request->user();
        $point = PointOfSale::query()->with('company')->findOrFail($data['point_of_sale_id']);

        $allowed = $user->is_master_admin
            || ($user->is_company_admin && (int) $user->company_id === (int) $point->company_id)
            || $user->pointsOfSale()->whereKey($point->id)->exists();
        abort_unless($allowed && $point->is_active && $point->company->status === 'active', 403, 'Accès à ce point de vente interdit.');

        $user->forceFill([
            'company_id' => $point->company_id,
            'current_establishment_id' => $point->establishment_id,
            'current_point_of_sale_id' => $point->id,
        ])->save();
        $request->session()->put([
            'company_id' => $point->company_id,
            'point_of_sale_id' => $point->id,
        ]);

        return redirect()->route('dashboard')->with('success', "Point de vente actif : {$point->name}.");
    }
}
