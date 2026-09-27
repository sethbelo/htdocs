<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKitchenModuleEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->establishment?->kitchenModuleEnabled(),
            403,
            'Le module Cuisine est désactivé pour cet établissement.'
        );

        return $next($request);
    }
}
