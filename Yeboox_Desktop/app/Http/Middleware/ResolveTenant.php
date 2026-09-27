<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\PointOfSale;
use App\Models\User;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/** Résout et valide le locataire avant toute liaison de modèle métier. */
class ResolveTenant
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        // Une instance créée dans la requête courante peut ne pas encore avoir
        // rechargé la valeur par défaut SQL. Seul un `false` explicite bloque.
        abort_if($user->is_active === false, 403, 'Ce compte utilisateur est désactivé.');
        $this->attachLegacyUserToSingleTenant($user);

        $requestedCompanyId = $this->integerHeader($request, 'X-Company-ID');
        $requestedPointId = $this->integerHeader($request, 'X-Point-of-Sale-ID');
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $requestedCompanyId ??= $token->company_id;
            $requestedPointId ??= $token->point_of_sale_id;
        } else {
            $requestedCompanyId ??= (int) $request->session()->get('company_id', $user->company_id);
            $requestedPointId ??= (int) $request->session()->get('point_of_sale_id', $user->current_point_of_sale_id);
        }

        if ($user->is_master_admin && ! $requestedCompanyId) {
            $this->context->set(null, null, null, bypass: true);

            if ($request->is('api/*')) {
                abort(409, 'Sélectionnez une entreprise et un point de vente pour accéder aux données métier.');
            }

            return redirect()->route('master.companies');
        }

        $companyId = $requestedCompanyId ?: $user->company_id;
        abort_unless($companyId, 403, 'Aucune entreprise n’est associée à ce compte.');
        abort_unless($user->is_master_admin || (int) $user->company_id === (int) $companyId, 403, 'Accès inter-entreprise interdit.');

        $company = Company::query()->whereKey($companyId)->where('status', 'active')->firstOrFail();
        $pointOfSale = $this->resolvePointOfSale($user, $company, $requestedPointId);

        $this->context->set($company, $pointOfSale?->establishment, $pointOfSale);
        $request->attributes->set('tenant', $this->context);

        try {
            return $next($request);
        } finally {
            $this->context->clear();
        }
    }

    private function resolvePointOfSale(User $user, Company $company, ?int $requestedId): ?PointOfSale
    {
        $pointId = $requestedId ?: $user->current_point_of_sale_id;
        if (! $pointId) {
            $fallback = PointOfSale::query()
                ->with('establishment')
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->when(
                    ! $user->is_master_admin && ! $user->is_company_admin,
                    fn ($query) => $query->whereHas('users', fn ($users) => $users->whereKey($user->id)),
                )
                ->orderBy('id')
                ->first();

            if ($fallback) {
                $user->forceFill([
                    'current_establishment_id' => $fallback->establishment_id,
                    'current_point_of_sale_id' => $fallback->id,
                ])->save();
            }

            return $fallback;
        }

        $point = PointOfSale::query()
            ->with('establishment')
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->findOrFail($pointId);

        $canAccess = $user->is_master_admin
            || $user->is_company_admin
            || $user->pointsOfSale()->whereKey($point->id)->exists();
        abort_unless($canAccess, 403, 'Vous n’avez pas accès à ce point de vente.');

        return $point;
    }

    private function integerHeader(Request $request, string $name): ?int
    {
        $value = filter_var($request->header($name), FILTER_VALIDATE_INT);

        return $value && $value > 0 ? $value : null;
    }

    /**
     * Compatibilité pour les comptes créés par une ancienne installation,
     * une invitation inachevée ou une factory de test avant le choix du tenant.
     */
    private function attachLegacyUserToSingleTenant(User $user): void
    {
        if ($user->company_id || Company::query()->count() !== 1) {
            return;
        }

        $company = Company::query()->firstOrFail();
        $point = $company->pointsOfSale()->where('is_active', true)->first();
        abort_unless($point, 409, 'Aucun point de vente actif n’est configuré.');

        $user->forceFill([
            'company_id' => $company->id,
            'current_establishment_id' => $point->establishment_id,
            'current_point_of_sale_id' => $point->id,
        ])->save();
        $user->pointsOfSale()->syncWithoutDetaching([
            $point->id => ['access_level' => $user->is_company_admin ? 'administrator' : 'operator'],
        ]);
    }
}
