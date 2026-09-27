<?php

namespace App\Livewire\Concerns;

use App\Exceptions\BusinessRuleException;
use Closure;
use Flux\Flux;

/**
 * Exécute une action métier et affiche le résultat (ou la règle violée) dans un toast Flux.
 */
trait InteractsWithToasts
{
    protected function attempt(Closure $action, ?string $success = null): mixed
    {
        try {
            $result = $action();

            if ($success) {
                Flux::toast(text: $success, variant: 'success');
            }

            return $result;
        } catch (BusinessRuleException $exception) {
            Flux::toast(text: $exception->getMessage(), heading: 'Action impossible', variant: 'danger');

            return null;
        }
    }

    protected function toast(string $text, string $variant = 'success', ?string $heading = null): void
    {
        Flux::toast(text: $text, heading: $heading, variant: $variant);
    }
}
