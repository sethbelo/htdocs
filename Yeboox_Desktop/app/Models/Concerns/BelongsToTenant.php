<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\Establishment;
use App\Models\PointOfSale;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ajoute le cloisonnement locataire aux modèles métier.
 *
 * Le scope n'est actif qu'après résolution du contexte HTTP. Les migrations,
 * seeders et commandes de maintenance restent donc utilisables explicitement.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            if (! app()->bound(TenantContext::class)) {
                return;
            }

            $context = app(TenantContext::class);
            if (! $context->active()) {
                return;
            }

            $model = $builder->getModel();
            $builder->where($model->qualifyColumn('company_id'), $context->companyId());

            $level = method_exists($model, 'tenantScopeLevel')
                ? $model->tenantScopeLevel()
                : 'point_of_sale';

            if ($level === 'establishment' && $context->establishmentId()) {
                $builder->where($model->qualifyColumn('establishment_id'), $context->establishmentId());
            }

            if ($level === 'point_of_sale' && $context->pointOfSaleId()) {
                $builder->where($model->qualifyColumn('point_of_sale_id'), $context->pointOfSaleId());
            }
        });

        static::creating(function ($model): void {
            $level = method_exists($model, 'tenantScopeLevel')
                ? $model->tenantScopeLevel()
                : 'point_of_sale';
            $context = app()->bound(TenantContext::class)
                ? app(TenantContext::class)
                : null;

            if ($context?->active()) {
                $model->company_id ??= $context->companyId();
                $model->establishment_id ??= $context->establishmentId();
                $model->point_of_sale_id ??= $context->pointOfSaleId();

                return;
            }

            // Compatibilité de l'édition Desktop historique : une commande,
            // un seeder ou un test peut écrire avant la première requête HTTP.
            // Le raccourci n'est permis que lorsqu'un seul tenant existe.
            if ($model->company_id) {
                if ($level === 'establishment' && ! $model->establishment_id) {
                    throw new \LogicException('Un établissement est obligatoire pour cette écriture.');
                }
                if ($level === 'point_of_sale' && ! $model->point_of_sale_id) {
                    throw new \LogicException('Un point de vente est obligatoire pour cette écriture.');
                }

                return;
            }

            $companyCount = Company::query()->count();
            if ($companyCount > 1) {
                throw new \LogicException('Un contexte entreprise et point de vente est obligatoire pour cette écriture.');
            }
            if ($companyCount === 0) {
                return;
            }

            $company = Company::query()->first();
            $point = $company?->pointsOfSale()->where('is_active', true)->first();
            $model->company_id = $company?->id;
            $model->establishment_id ??= $point?->establishment_id;
            $model->point_of_sale_id ??= $point?->id;
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }

    public function pointOfSale(): BelongsTo
    {
        return $this->belongsTo(PointOfSale::class);
    }
}
