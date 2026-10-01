<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Lecture du registre config/erp.php + activation par société.
 */
final class Modules
{
    public static function all(): array
    {
        return config('erp.modules');
    }

    public static function get(string $key): ?array
    {
        return config("erp.modules.{$key}");
    }

    public static function enabled(string $key): bool
    {
        $module = self::get($key);
        if (! $module) {
            return false;
        }
        if ($module['core'] ?? false) {
            return true;
        }

        return ! in_array($key, (array) Tenant::setting('modules_disabled', []), true);
    }

    /** Navigation groupée, filtrée par droits et activation. */
    public static function navigation(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        return collect(self::all())
            ->filter(fn ($module, $key) => $user->canAccessModule($key))
            ->map(fn ($module, $key) => $module + ['key' => $key])
            ->groupBy('group')
            ->sortBy(fn ($items, $group) => array_search($group, array_keys(config('erp.groups')), true));
    }

    /** Liste exhaustive des permissions générées depuis le registre. */
    public static function permissions(): array
    {
        $permissions = [];
        foreach (self::all() as $key => $module) {
            $permissions[] = "{$key}.view";
            $permissions[] = "{$key}.manage";
            foreach ($module['actions'] ?? [] as $action) {
                $permissions[] = "{$key}.{$action}";
            }
        }

        return $permissions;
    }

    /**
     * Description courte de chaque application (groupe du registre), affichée dans le lanceur.
     *
     * @var array<string, string>
     */
    public const APP_DESCRIPTIONS = [
        'personnel' => 'Notes, tableurs, bons, congés et tâches du jour',
        'pilotage' => 'Tableau de bord et suivi des commandes',
        'relation' => 'Accueil, pipeline, clients et fidélité',
        'ventes' => 'Devis, factures, point de vente et location',
        'operations' => 'Atelier, chantiers, projets et planning',
        'stock' => 'Entrepôts, achats et bons',
        'finance' => 'Caisse, comptabilité, trésorerie et rapports',
        'rh' => 'Employés, congés et paie',
        'systeme' => 'Messagerie et paramétrage',
    ];

    /**
     * Applications accessibles (une par groupe du registre), chacune avec ses rubriques.
     * Un module peut détailler ses écrans via une clé « menu » : [['label', 'icon', 'route'], …].
     *
     * @return list<array{key: string, label: string, description: string, items: list<array{key: string, module: string, label: string, icon: string, route: string, url: string}>}>
     */
    public static function apps(?User $user): array
    {
        $groups = config('erp.groups');

        return self::navigation($user)->map(function (Collection $modules, string $group) use ($groups) {
            $items = $modules->flatMap(function (array $module) {
                $entries = $module['menu'] ?? [['label' => $module['label'], 'icon' => $module['icon'], 'route' => $module['route']]];

                return collect($entries)
                    ->filter(fn (array $entry) => Route::has($entry['route']))
                    ->map(fn (array $entry) => [
                        'key' => AppIcons::has($entry['route']) ? $entry['route'] : $module['key'],
                        'module' => $module['key'],
                        'label' => $entry['label'],
                        'icon' => $entry['icon'] ?? $module['icon'],
                        'route' => $entry['route'],
                        'url' => route($entry['route']),
                    ]);
            })->values()->all();

            return ['key' => $group, 'label' => $groups[$group] ?? Str::headline($group), 'description' => self::APP_DESCRIPTIONS[$group] ?? '', 'items' => $items];
        })->filter(fn (array $app) => $app['items'] !== [])->values()->all();
    }

    /**
     * Module de la page affichée, lu dans le middleware « module:{clé} » de la route.
     */
    public static function currentModule(): ?string
    {
        $route = request()->route();
        if (! $route) {
            return null;
        }
        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'module:')) {
                return substr($middleware, 7);
            }
        }
        foreach (self::all() as $key => $module) {
            if (request()->routeIs($module['route'])) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Application (groupe) active : celle du module affiché, sinon la dernière ouverte.
     * La barre latérale ne présente que les rubriques de cette application.
     */
    public static function currentApp(?User $user): ?array
    {
        $apps = collect(self::apps($user));
        $module = self::currentModule();
        $group = $module ? (self::get($module)['group'] ?? null) : null;

        if ($group && $apps->contains('key', $group)) {
            session(['erp.app' => $group]);
        } else {
            $group = session('erp.app');
        }

        return $apps->firstWhere('key', $group) ?? $apps->firstWhere('key', 'pilotage') ?? $apps->first();
    }

    /** Une rubrique du menu correspond-elle à la page affichée ? */
    public static function isActive(array $item): bool
    {
        if (request()->routeIs($item['route'])) {
            return true;
        }
        $module = self::get($item['module']);
        if (! empty($module['menu'])) {
            // Menu détaillé : sous-pages d'une rubrique, sauf si une rubrique plus précise correspond.
            $siblings = collect($module['menu'])->pluck('route')->reject(fn ($route) => $route === $item['route'] || ! str_starts_with($route, $item['route'].'.'));

            return request()->routeIs($item['route'].'.*') && ! $siblings->contains(fn ($route) => request()->routeIs($route, $route.'.*'));
        }
        $prefix = Str::beforeLast($item['route'], '.');

        return request()->routeIs($item['route'].'.*') || ($prefix !== $item['route'] && request()->routeIs($prefix.'.*')) || self::currentModule() === $item['module'];
    }
}
