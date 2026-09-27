<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Expiration facultative de session par utilisateur (inactivité), et blocage des comptes désactivés.
 */
class EnforceSessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            return $this->logout($request, 'Votre compte est désactivé.');
        }

        if ($user?->session_timeout_minutes) {
            $lastActivity = (int) $request->session()->get('last_activity_at', now()->timestamp);

            if (now()->timestamp - $lastActivity > $user->session_timeout_minutes * 60) {
                return $this->logout($request, 'Session expirée après inactivité.');
            }

            $request->session()->put('last_activity_at', now()->timestamp);
        }

        return $next($request);
    }

    private function logout(Request $request, string $message): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', $message);
    }
}
