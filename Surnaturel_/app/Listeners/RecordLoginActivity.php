<?php

namespace App\Listeners;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Journal des connexions : succès, échecs et déconnexions.
 */
class RecordLoginActivity
{
    public function handleLogin(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill(['last_login_at' => now(), 'last_login_ip' => request()->ip()])->saveQuietly();

        $history = LoginHistory::query()->create([
            'user_id' => $event->user->id,
            'identifier' => $event->user->username,
            'method' => session()->pull('login_method', 'password'),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'succeeded' => true,
            'logged_in_at' => now(),
        ]);

        if (request()->hasSession()) {
            request()->session()->put('login_history_id', $history->id);
        }
    }

    public function handleFailed(Failed $event): void
    {
        LoginHistory::query()->create([
            'user_id' => $event->user?->getAuthIdentifier(),
            'identifier' => (string) ($event->credentials['email'] ?? $event->credentials['username'] ?? '?'),
            'method' => $event->credentials['method'] ?? 'password',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'succeeded' => false,
            'failure_reason' => 'Identifiants invalides',
            'logged_in_at' => now(),
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        if (request()->hasSession() && ($id = request()->session()->get('login_history_id'))) {
            LoginHistory::query()->whereKey($id)->update(['logged_out_at' => now()]);
        }
    }
}
