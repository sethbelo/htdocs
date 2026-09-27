<?php

namespace App\Tenancy;

use App\Models\Company;
use App\Models\Establishment;
use App\Models\PointOfSale;
use Closure;

/**
 * Contexte locataire de la requête courante.
 *
 * Cette classe est enregistrée avec une durée de vie « scoped » afin qu'un
 * worker persistant ne puisse jamais réutiliser le locataire précédent.
 */
final class TenantContext
{
    private ?Company $company = null;

    private ?Establishment $establishment = null;

    private ?PointOfSale $pointOfSale = null;

    private bool $bypassed = false;

    public function set(
        ?Company $company,
        ?Establishment $establishment = null,
        ?PointOfSale $pointOfSale = null,
        bool $bypass = false,
    ): self {
        $this->company = $company;
        $this->establishment = $establishment;
        $this->pointOfSale = $pointOfSale;
        $this->bypassed = $bypass;

        return $this;
    }

    public function clear(): void
    {
        $this->company = null;
        $this->establishment = null;
        $this->pointOfSale = null;
        $this->bypassed = false;
    }

    public function active(): bool
    {
        return $this->company !== null && ! $this->bypassed;
    }

    public function bypassed(): bool
    {
        return $this->bypassed;
    }

    public function company(): ?Company
    {
        return $this->company;
    }

    public function establishment(): ?Establishment
    {
        return $this->establishment;
    }

    public function pointOfSale(): ?PointOfSale
    {
        return $this->pointOfSale;
    }

    public function companyId(): ?int
    {
        return $this->company?->id;
    }

    public function establishmentId(): ?int
    {
        return $this->establishment?->id;
    }

    public function pointOfSaleId(): ?int
    {
        return $this->pointOfSale?->id;
    }

    /** Exécute une opération explicite dans un autre contexte puis restaure le précédent. */
    public function run(
        Company $company,
        ?Establishment $establishment,
        ?PointOfSale $pointOfSale,
        Closure $callback,
    ): mixed {
        $previous = [$this->company, $this->establishment, $this->pointOfSale, $this->bypassed];
        $this->set($company, $establishment, $pointOfSale);

        try {
            return $callback();
        } finally {
            [$this->company, $this->establishment, $this->pointOfSale, $this->bypassed] = $previous;
        }
    }
}
