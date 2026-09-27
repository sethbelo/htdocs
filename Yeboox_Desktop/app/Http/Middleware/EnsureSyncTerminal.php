<?php

namespace App\Http\Middleware;

use App\Models\PosSyncCredential;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSyncTerminal
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = trim((string) $request->header('X-Yeboox-Sync-Token'));
        if ($token === '') {
            return response()->json(['message' => 'La clé de synchronisation du terminal est absente.'], 401);
        }

        $hash = hash('sha256', $token);
        if (! PosSyncCredential::query()->where('token_hash', $hash)->where('is_active', true)->exists()) {
            return response()->json(['message' => 'Ce terminal n’est pas autorisé à synchroniser.'], 401);
        }

        $request->attributes->set('sync_token_hash', $hash);

        return $next($request);
    }
}
