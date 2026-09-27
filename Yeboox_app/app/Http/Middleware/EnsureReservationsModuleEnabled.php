<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureReservationsModuleEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->establishment?->reservationsModuleEnabled(),
            403,
            'Le module Réservations est désactivé pour cet établissement.'
        );

        return $next($request);
    }
}
