<?php

namespace App\Http\Middleware;

use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActivePosSubscription
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_master_admin) {
            return $next($request);
        }

        $point = $this->context->pointOfSale();
        abort_unless($point, 409, 'Sélectionnez un point de vente pour continuer.');
        abort_unless(
            $point->hasActiveSubscription(),
            402,
            'L’abonnement de ce point de vente est expiré ou suspendu.',
        );

        return $next($request);
    }
}
