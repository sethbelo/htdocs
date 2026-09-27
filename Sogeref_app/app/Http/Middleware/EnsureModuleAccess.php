<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vérifie qu'un module est activé pour la société et autorisé pour
 * l'utilisateur : Route::middleware('module:payroll').
 */
class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();
        abort_unless($user && $user->canAccessModule($module), 403, 'Accès non autorisé à ce module.');

        return $next($request);
    }
}
