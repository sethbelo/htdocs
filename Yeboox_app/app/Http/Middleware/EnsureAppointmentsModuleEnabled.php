<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppointmentsModuleEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->establishment?->appointmentsModuleEnabled(),
            403,
            'Le module Rendez-vous est désactivé pour cet établissement.'
        );

        return $next($request);
    }
}
