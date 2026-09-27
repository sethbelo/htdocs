<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictCaissier
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->hasRole('Caissier')) {
            $allowedRoutes = [
                'dashboard',
                'pos',
                'appointments',
                'customers',
                'profile.show',
                'logout',
                'login',
            ];
            $currentRoute = $request->route() ? $request->route()->getName() : null;

            if ($currentRoute && ! in_array($currentRoute, $allowedRoutes, true) && ! $request->is('livewire/*')) {
                abort(403, 'Vous n’avez pas accès à ce module.');
            }
        }

        return $next($request);
    }
}
