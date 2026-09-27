<?php

use App\Http\Middleware\EnsureActivePosSubscription;
use App\Http\Middleware\EnsureApiAccess;
use App\Http\Middleware\EnsureCompanyAdministrator;
use App\Http\Middleware\EnsureMasterAdministrator;
use App\Http\Middleware\EnsureSyncTerminal;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$application = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'api.access' => EnsureApiAccess::class,
            'tenant' => ResolveTenant::class,
            'pos.subscription' => EnsureActivePosSubscription::class,
            'company.admin' => EnsureCompanyAdministrator::class,
            'master.admin' => EnsureMasterAdministrator::class,
            'sync.terminal' => EnsureSyncTerminal::class,
        ]);

        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            ResolveTenant::class,
        );

        // Les contrôles d'accès sont déclarés par permission sur chaque route.
        // Cela permet aussi à Livewire de les rejouer sur ses requêtes suivantes.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Les API locales renvoient elles aussi des messages directement
        // compréhensibles par l'utilisateur, sans exposer les détails internes.
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Vous devez vous connecter pour continuer.'], 401);
            }
        });
        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Vous n’êtes pas autorisé à effectuer cette action.'], 403);
            }
        });
        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'La ressource demandée est introuvable.'], 404);
            }
        });
        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'La route ou la ressource demandée est introuvable.'], 404);
            }
        });
    })->create();

// Electron keeps writable data outside the installed application directory.
// Bind that location explicitly before Laravel loads its configuration so
// Blade never resolves its compiled-view directory to `false` on Windows.
$electronStoragePath = getenv('LARAVEL_STORAGE_PATH');

if (is_string($electronStoragePath) && trim($electronStoragePath) !== '') {
    $application->useStoragePath($electronStoragePath);
}

return $application;
