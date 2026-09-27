<?php

namespace App\Http\Controllers\Api\V2\Company;

use App\Http\Controllers\Controller;
use App\Models\PointOfSale;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(TenantContext $tenant): JsonResponse
    {
        $users = User::query()
            ->where('company_id', $tenant->companyId())
            ->with(['roles:id,name', 'pointsOfSale:id,name,code'])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $users]);
    }

    public function store(Request $request, TenantContext $tenant): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
            'role' => ['required', Rule::in(['Gérant', 'Caissier'])],
            'point_of_sale_ids' => ['required', 'array', 'min:1'],
            'point_of_sale_ids.*' => ['integer', 'distinct'],
        ]);
        $points = PointOfSale::query()
            ->where('company_id', $tenant->companyId())
            ->whereIn('id', $data['point_of_sale_ids'])
            ->get();
        abort_unless($points->count() === count($data['point_of_sale_ids']), 422, 'Un point de vente est invalide.');

        $first = $points->first();
        $user = User::create([
            'company_id' => $tenant->companyId(),
            'current_establishment_id' => $first->establishment_id,
            'current_point_of_sale_id' => $first->id,
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
            'is_company_admin' => false,
            'is_master_admin' => false,
            'is_active' => true,
        ]);
        $user->assignRole(Role::findOrCreate($data['role'], 'web'));
        $user->pointsOfSale()->syncWithPivotValues($points->pluck('id'), [
            'access_level' => $data['role'] === 'Gérant' ? 'manager' : 'operator',
        ]);

        return response()->json(['message' => 'Utilisateur créé.', 'data' => $user->load(['roles', 'pointsOfSale'])], 201);
    }

    public function update(Request $request, User $user, TenantContext $tenant): JsonResponse
    {
        abort_unless((int) $user->company_id === $tenant->companyId(), 404);
        abort_if($user->is_master_admin, 403, 'Le compte master ne peut pas être modifié ici.');
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:190'],
            'email' => ['sometimes', 'email', 'max:190', Rule::unique('users')->ignore($user)],
            'password' => ['nullable', 'string', 'min:10', 'confirmed'],
            'role' => ['sometimes', Rule::in(['Super administrateur', 'Gérant', 'Caissier'])],
            'point_of_sale_ids' => ['sometimes', 'array', 'min:1'],
            'point_of_sale_ids.*' => ['integer', 'distinct'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $user->update([
            ...collect($data)->only(['name', 'email', 'is_active'])->all(),
            ...filled($data['password'] ?? null) ? ['password' => Hash::make($data['password'])] : [],
        ]);
        if (isset($data['role'])) {
            $user->syncRoles([Role::findOrCreate($data['role'], 'web')]);
            $user->is_company_admin = $data['role'] === 'Super administrateur';
            $user->save();
        }
        if (isset($data['point_of_sale_ids'])) {
            $validIds = PointOfSale::query()
                ->where('company_id', $tenant->companyId())
                ->whereIn('id', $data['point_of_sale_ids'])
                ->pluck('id');
            abort_unless($validIds->count() === count($data['point_of_sale_ids']), 422, 'Un point de vente est invalide.');
            $user->pointsOfSale()->syncWithPivotValues($validIds, ['access_level' => 'operator']);
        }

        return response()->json(['message' => 'Utilisateur mis à jour.', 'data' => $user->refresh()->load(['roles', 'pointsOfSale'])]);
    }
}
