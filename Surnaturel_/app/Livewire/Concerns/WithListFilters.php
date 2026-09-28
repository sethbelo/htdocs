<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

/**
 * Filtres communs des listes : recherche plein texte et période ; les filtres propres à l'écran
 * (statut, type, entrepôt…) sont déclarés par le composant et listés dans filterProperties().
 */
trait WithListFilters
{
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'du', except: '')]
    public string $from = '';

    #[Url(as: 'au', except: '')]
    public string $to = '';

    /**
     * @return list<string>
     */
    protected function filterProperties(): array
    {
        return property_exists($this, 'status') ? ['status'] : [];
    }

    public function updatingSearch(): void
    {
        $this->resetPageIfPaginated();
    }

    public function updatingFrom(): void
    {
        $this->resetPageIfPaginated();
    }

    public function updatingTo(): void
    {
        $this->resetPageIfPaginated();
    }

    private function resetPageIfPaginated(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'from', 'to', ...$this->filterProperties());
        $this->resetPageIfPaginated();
    }

    public function hasActiveFilters(): bool
    {
        $defaults = (new \ReflectionClass($this))->getDefaultProperties();

        return collect(['search', 'from', 'to', ...$this->filterProperties()])
            ->contains(fn (string $p) => $this->{$p} !== ($defaults[$p] ?? null) && filled($this->{$p}));
    }

    /**
     * @param  list<string>  $columns  colonnes locales ou « relation.colonne »
     */
    protected function applySearch(Builder $query, array $columns): Builder
    {
        $term = trim($this->search);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($columns, $term): void {
            foreach ($columns as $column) {
                if (str_contains($column, '.')) {
                    [$relation, $field] = explode('.', $column, 2);
                    $q->orWhereHas($relation, fn (Builder $r) => $r->where($field, 'like', "%{$term}%"));
                } else {
                    $q->orWhere($q->getModel()->qualifyColumn($column), 'like', "%{$term}%");
                }
            }
        });
    }

    protected function applyPeriod(Builder $query, string $column): Builder
    {
        return $query
            ->when($this->from, fn (Builder $q) => $q->whereDate($q->getModel()->qualifyColumn($column), '>=', $this->from))
            ->when($this->to, fn (Builder $q) => $q->whereDate($q->getModel()->qualifyColumn($column), '<=', $this->to));
    }
}
