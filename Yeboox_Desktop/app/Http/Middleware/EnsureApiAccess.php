<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAccess
{
    public function handle(Request $request, Closure $next, string $ability, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user || ! $user->tokenCan($ability)) {
            return $this->forbidden('Ce jeton ne possède pas la capacité requise.');
        }

        if ($permissions !== [] && ! Gate::forUser($user)->any($permissions)) {
            return $this->forbidden('Votre rôle ne possède plus la permission requise.');
        }

        return $next($request);
    }

    private function forbidden(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'error' => ['code' => 'API_ACCESS_DENIED'],
        ], 403);
    }
}
