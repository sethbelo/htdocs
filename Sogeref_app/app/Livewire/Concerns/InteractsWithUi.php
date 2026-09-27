<?php

namespace App\Livewire\Concerns;

use App\Support\Approvals;
use DomainException;

/**
 * Outils communs aux écrans Livewire : notifications, contrôle des droits
 * et exécution protégée des actions métier.
 */
trait InteractsWithUi
{
    protected function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('toast', type: $type, message: $message);
    }

    protected function authorizeModule(string $module, string $ability = 'view'): void
    {
        $user = auth()->user();
        $allowed = $ability === 'view' ? $user->canAccessModule($module)
            : ($ability === 'manage' ? $user->canManage($module) : ($user->isMaster() || $user->can("{$module}.{$ability}")));
        abort_unless($allowed, 403, 'Action non autorisée pour votre rôle.');
    }

    protected function allowed(string $permission): bool
    {
        $user = auth()->user();

        return $user->isMaster() || $user->can($permission);
    }

    /** Exécute une action métier ; les règles violées deviennent une notification. */
    protected function attempt(callable $callback, ?string $success = null): mixed
    {
        try {
            $result = $callback();
            if ($success) {
                $this->toast($success);
            }
            Approvals::forget();

            return $result ?? true;
        } catch (DomainException $exception) {
            $this->toast($exception->getMessage(), 'error');

            return false;
        }
    }
}
