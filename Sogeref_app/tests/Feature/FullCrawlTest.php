<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use Tests\TestCase;
use Throwable;

/**
 * Robot de non-régression : chaque page GET de l'ERP est ouverte avec chaque
 * profil. Aucune ne doit produire d'erreur serveur (500) : seules les
 * réponses 200, 302, 403 et 404 sont admises.
 */
class FullCrawlTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /** Routes exclues : techniques, fichiers, déconnexion. */
    private const SKIP = ['livewire', 'storage', 'up', 'login', 'logout', 'webhooks', 'sanctum', 'ignition', 'desktop'];

    public function test_no_page_crashes_for_any_role(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RoutingRoute $r) => in_array('GET', $r->methods(), true) && $r->getName() && ! Str::startsWith($r->getName(), self::SKIP))
            ->filter(fn (RoutingRoute $r) => in_array('auth', $r->gatherMiddleware(), true));

        $failures = [];
        $visited = 0;
        foreach (User::orderBy('id')->get() as $user) {
            $this->actingAs($user);
            foreach ($routes as $route) {
                $url = $this->urlFor($route);
                if ($url === null) {
                    continue;
                }
                $visited++;
                try {
                    $status = $this->get($url)->getStatusCode();
                } catch (Throwable $e) {
                    $status = 'exception: '.Str::limit($e->getMessage(), 200);
                }
                if (! in_array($status, [200, 302, 403, 404], true)) {
                    $failures[] = "{$user->email} → {$route->getName()} ({$url}) : {$status}";
                }
                $this->app['auth']->forgetGuards();
                $this->actingAs($user);
            }
        }

        $this->assertGreaterThan(70 * User::count(), $visited, "Trop peu de pages parcourues ({$visited}).");
        $this->assertSame([], $failures, "Pages en erreur :\n".implode("\n", $failures));
    }

    /** URL de la route, paramètres remplacés par le premier enregistrement du modèle lié. */
    private function urlFor(RoutingRoute $route): ?string
    {
        $parameters = [];
        foreach ($route->parameterNames() as $name) {
            $model = $this->modelFor($route, $name);
            if ($model === null) {
                return null;
            }
            $record = $model::query()->first();
            if (! $record) {
                return null;
            }
            $parameters[$name] = $record;
        }
        try {
            return route($route->getName(), $parameters + ($route->defaults ?? []));
        } catch (Throwable) {
            return null;
        }
    }

    /** @return class-string<Model>|null */
    private function modelFor(RoutingRoute $route, string $name): ?string
    {
        $action = $route->getAction('uses');
        try {
            $reflection = match (true) {
                $action instanceof \Closure => new ReflectionFunction($action),
                is_string($action) && str_contains($action, '@') => new ReflectionMethod(...explode('@', $action)),
                is_string($action) && method_exists($action, 'mount') => new ReflectionMethod($action, 'mount'),
                default => null,
            };
        } catch (Throwable) {
            return null;
        }
        foreach ($reflection?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();
            if ($parameter->getName() === $name && $type instanceof ReflectionNamedType && is_subclass_of($type->getName(), Model::class)) {
                return $type->getName();
            }
        }

        return null;
    }
}
