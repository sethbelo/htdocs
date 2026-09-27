<?php

namespace App\Providers;

use App\Http\Middleware\ResolveTenant;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantContext::class, fn () => new TenantContext);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::addPersistentMiddleware([ResolveTenant::class]);
        // Le Super administrateur passe par le Gate Laravel ; tous les autres
        // utilisateurs restent soumis aux permissions réellement attribuées.
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->hasRole('Super administrateur') ? true : null;
        });

        RateLimiter::for('api-login', fn (Request $request) => Limit::perMinute(5)->by(
            mb_strtolower((string) $request->input('email')).'|'.$request->ip()
        ));
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by(
            (string) ($request->user()?->id ?: $request->ip())
        ));
        RateLimiter::for('sync', fn (Request $request) => Limit::perMinute(300)->by(
            hash('sha256', (string) $request->header('X-Yeboox-Sync-Token')).'|'.$request->ip()
        ));
    }
}
