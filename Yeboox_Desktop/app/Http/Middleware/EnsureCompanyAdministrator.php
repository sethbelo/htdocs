<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user?->is_master_admin || $user?->is_company_admin, 403, 'Accès réservé à l’administrateur de l’entreprise.');

        return $next($request);
    }
}
