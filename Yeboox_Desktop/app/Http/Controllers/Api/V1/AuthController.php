<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PointOfSale;
use App\Models\User;
use App\Support\ApiAbilityCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['required', 'string', 'max:80'],
            'point_of_sale_id' => ['nullable', 'integer', 'exists:points_of_sale,id'],
        ]);

        $user = User::query()->where('email', mb_strtolower($data['email']))->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Les identifiants fournis sont incorrects.']);
        }
        if (! $user->is_active) {
            throw ValidationException::withMessages(['email' => 'Ce compte utilisateur est désactivé.']);
        }

        $pointOfSaleId = (int) ($data['point_of_sale_id'] ?? $user->current_point_of_sale_id);
        $point = null;
        if ($pointOfSaleId) {
            $point = PointOfSale::query()
                ->when(! $user->is_master_admin, fn ($query) => $query->where('company_id', $user->company_id))
                ->where('is_active', true)
                ->findOrFail($pointOfSaleId);
            $allowed = $user->is_master_admin
                || $user->is_company_admin
                || $user->pointsOfSale()->whereKey($point->id)->exists();
            abort_unless($allowed, 403, 'Vous n’avez pas accès à ce point de vente.');
        }

        $abilities = ApiAbilityCatalog::for($user);
        $expiresAt = now()->addMinutes((int) config('sanctum.mobile_expiration', 43200));
        $tokenName = 'Android · '.trim($data['device_name']);

        // Un appareil remplace son ancien jeton afin de limiter les sessions oubliées.
        $user->tokens()->where('name', $tokenName)->delete();
        $token = $user->createToken($tokenName, $abilities, $expiresAt);
        $token->accessToken->forceFill([
            'company_id' => $point?->company_id ?? $user->company_id,
            'point_of_sale_id' => $pointOfSaleId ?: null,
        ])->save();

        return response()->json(['data' => [
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toISOString(),
            'abilities' => $abilities,
            'user' => $this->userData($user),
            'tenant' => $this->tenantData($user, $pointOfSaleId ?: null, $point?->company_id),
        ]]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->userData($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'L’appareil a été déconnecté.']);
    }

    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'photo_url' => $user->profile_photo_url,
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            'is_master_admin' => (bool) $user->is_master_admin,
            'is_company_admin' => (bool) $user->is_company_admin,
            'company_id' => $user->company_id,
            'current_point_of_sale_id' => $user->current_point_of_sale_id,
        ];
    }

    private function tenantData(User $user, ?int $pointOfSaleId = null, ?int $selectedCompanyId = null): array
    {
        $company = $selectedCompanyId ? Company::query()->find($selectedCompanyId) : $user->company;
        $points = ($user->is_master_admin && ! $company)
            ? collect()
            : ($user->is_master_admin || $user->is_company_admin
            ? $company?->pointsOfSale()->with('establishment')->where('is_active', true)->get() ?? collect()
            : $user->pointsOfSale()->with('establishment')->where('points_of_sale.is_active', true)->get());

        return [
            'company' => $company ? [
                'id' => $company->id,
                'uuid' => $company->uuid,
                'name' => $company->name,
                'status' => $company->status,
            ] : null,
            'current_point_of_sale_id' => $pointOfSaleId ?? $user->current_point_of_sale_id,
            'points_of_sale' => $points->map(fn (PointOfSale $point) => [
                'id' => $point->id,
                'uuid' => $point->uuid,
                'name' => $point->name,
                'code' => $point->code,
                'establishment' => $point->establishment?->name,
                'subscription' => $point->activeSubscription()?->only(['plan_code', 'status', 'starts_at', 'ends_at']),
            ])->values(),
        ];
    }
}
