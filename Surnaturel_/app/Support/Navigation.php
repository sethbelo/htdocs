<?php

namespace App\Support;

use App\Models\Task;
use App\Models\User;
use App\Services\Approvals\ApprovalService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Menu latéral : chaque entrée n'apparaît que si l'utilisateur possède la permission requise.
 */
class Navigation
{
    /**
     * @return list<array{heading: string, items: list<array{label: string, icon: string, route: string, permission: string, match?: string}>}>
     */
    public static function groups(): array
    {
        return [
            ['heading' => 'Pilotage', 'items' => [
                ['label' => 'Tableau de bord', 'icon' => 'presentation-chart-line', 'route' => 'dashboard', 'permission' => 'dashboard.view'],
                ['label' => 'Mes validations', 'icon' => 'check-badge', 'route' => 'approvals.index', 'permission' => 'approvals.view'],
                ['label' => 'Tâches', 'icon' => 'clipboard-document-check', 'route' => 'tasks.index', 'permission' => 'tasks.view'],
                ['label' => 'Visa des paies', 'icon' => 'document-check', 'route' => 'direction.payroll', 'permission' => 'direction.payroll_sign'],
            ]],
            ['heading' => 'Commercial', 'items' => [
                ['label' => 'Clients', 'icon' => 'users', 'route' => 'clients.index', 'permission' => 'clients.view', 'match' => 'clients.*'],
                ['label' => 'Pipeline', 'icon' => 'funnel', 'route' => 'pipeline', 'permission' => 'pipeline.view'],
                ['label' => 'Devis & pro forma', 'icon' => 'document-text', 'route' => 'quotes.index', 'permission' => 'quotes.view', 'match' => 'quotes.*'],
                ['label' => 'Commandes', 'icon' => 'clipboard-document-list', 'route' => 'orders.index', 'permission' => 'orders.view', 'match' => 'orders.*'],
                ['label' => 'Fidélité', 'icon' => 'gift', 'route' => 'loyalty.index', 'permission' => 'loyalty.view'],
            ]],
            ['heading' => 'Marketing', 'items' => [
                ['label' => 'Suivi clients', 'icon' => 'phone-arrow-up-right', 'route' => 'followups.index', 'permission' => 'followups.view'],
            ]],
            ['heading' => 'Caisse & boutique', 'items' => [
                ['label' => 'Caisse commandes', 'icon' => 'banknotes', 'route' => 'cash.checkout', 'permission' => 'cash.collect'],
                ['label' => 'Point de vente', 'icon' => 'shopping-cart', 'route' => 'pos', 'permission' => 'pos.create'],
                ['label' => 'Sessions de caisse', 'icon' => 'calculator', 'route' => 'cash.sessions', 'permission' => 'cash.view'],
            ]],
            ['heading' => 'Production', 'items' => [
                ['label' => 'Graphisme', 'icon' => 'paint-brush', 'route' => 'design.board', 'permission' => 'design.view', 'match' => 'design.*'],
                ['label' => 'Salle d’impression', 'icon' => 'printer', 'route' => 'production.board', 'permission' => 'production.view'],
                ['label' => 'Parc machines', 'icon' => 'cog-6-tooth', 'route' => 'machines.index', 'permission' => 'machines.view'],
                ['label' => 'Livraisons', 'icon' => 'truck', 'route' => 'deliveries.index', 'permission' => 'deliveries.view'],
            ]],
            ['heading' => 'Logistique', 'items' => [
                ['label' => 'Articles', 'icon' => 'cube', 'route' => 'products.index', 'permission' => 'products.view'],
                ['label' => 'Stocks', 'icon' => 'archive-box', 'route' => 'inventory.index', 'permission' => 'inventory.view'],
                ['label' => 'Entrées & sorties', 'icon' => 'arrows-up-down', 'route' => 'inventory.operations', 'permission' => 'inventory.view'],
                ['label' => 'Transferts', 'icon' => 'arrows-right-left', 'route' => 'inventory.transfers', 'permission' => 'inventory.view'],
                ['label' => 'Rapport temps réel', 'icon' => 'signal', 'route' => 'inventory.report', 'permission' => 'inventory.view'],
                ['label' => 'Rapports d’entrepôt', 'icon' => 'document-chart-bar', 'route' => 'inventory.reports', 'permission' => 'inventory.view'],
                ['label' => 'Achats', 'icon' => 'shopping-bag', 'route' => 'purchases.index', 'permission' => 'purchases.view'],
                ['label' => 'Fournisseurs', 'icon' => 'building-storefront', 'route' => 'suppliers.index', 'permission' => 'suppliers.view'],
            ]],
            ['heading' => 'Finance', 'items' => [
                ['label' => 'Factures', 'icon' => 'document-currency-dollar', 'route' => 'invoices.index', 'permission' => 'invoices.view'],
                ['label' => 'Trésorerie', 'icon' => 'building-library', 'route' => 'treasury.index', 'permission' => 'treasury.view'],
                ['label' => 'Comptabilité', 'icon' => 'book-open', 'route' => 'accounting.index', 'permission' => 'accounting.view'],
                ['label' => 'Notes de frais', 'icon' => 'receipt-percent', 'route' => 'expenses.index', 'permission' => 'expenses.view'],
                ['label' => 'Bons', 'icon' => 'document-duplicate', 'route' => 'vouchers.index', 'permission' => 'vouchers.view'],
            ]],
            ['heading' => 'Ressources humaines', 'items' => [
                ['label' => 'Employés', 'icon' => 'user-group', 'route' => 'hr.employees', 'permission' => 'employees.view'],
                ['label' => 'Départements', 'icon' => 'rectangle-group', 'route' => 'hr.departments', 'permission' => 'departments.view'],
                ['label' => 'Présence', 'icon' => 'clock', 'route' => 'hr.attendance', 'permission' => 'attendance.view'],
                ['label' => 'Congés', 'icon' => 'calendar-days', 'route' => 'hr.leaves', 'permission' => 'leaves.view'],
                ['label' => 'Paie', 'icon' => 'wallet', 'route' => 'hr.payroll', 'permission' => 'payroll.view'],
                ['label' => 'Badges & cartes', 'icon' => 'identification', 'route' => 'hr.cards', 'permission' => 'cards.view'],
                ['label' => 'Santé & assurance', 'icon' => 'heart', 'route' => 'hr.affiliations', 'permission' => 'affiliations.view'],
            ]],
            ['heading' => 'Administration', 'items' => [
                ['label' => 'Documents', 'icon' => 'folder-open', 'route' => 'documents.index', 'permission' => 'documents.view'],
                ['label' => 'Rapports', 'icon' => 'chart-bar', 'route' => 'reports.index', 'permission' => 'reports.view'],
                ['label' => 'Audit', 'icon' => 'shield-check', 'route' => 'audit.index', 'permission' => 'audit.view'],
                ['label' => 'Paramètres', 'icon' => 'adjustments-horizontal', 'route' => 'settings.company', 'permission' => 'settings.view', 'match' => 'settings.*'],
            ]],
        ];
    }

    /**
     * @return list<array{heading: string, items: list<array<string, string>>}>
     */
    public static function for(User $user): array
    {
        $home = ['heading' => 'Mon espace', 'items' => [
            ['label' => 'Accueil', 'icon' => 'home', 'route' => 'home', 'permission' => '', 'match' => 'home'],
        ]];

        return collect([$home, ...self::groups()])
            ->map(fn (array $group): array => ['items' => array_values(array_filter(
                $group['items'],
                fn (array $item): bool => ($item['permission'] === '' || $user->can($item['permission'])) && Route::has($item['route']),
            ))] + $group)
            ->filter(fn (array $group): bool => $group['items'] !== [])
            ->values()
            ->all();
    }

    /**
     * Liste à plat des écrans accessibles, pour la palette de commandes (Ctrl/⌘ + K).
     *
     * @return list<array{label: string, group: string, url: string}>
     */
    public static function flat(User $user): array
    {
        return collect(self::for($user))
            ->flatMap(fn (array $group): array => collect($group['items'])
                ->map(fn (array $item): array => ['label' => $item['label'], 'group' => $group['heading'], 'url' => route($item['route'])])
                ->all())
            ->values()
            ->all();
    }

    /**
     * Icône « application » de chaque département (lanceur, sélecteur d'espace).
     *
     * @var array<string, array{icon: string, color: string, description: string}>
     */
    public const DEPARTMENTS = [
        'Pilotage' => ['icon' => 'dashboard', 'color' => '#134A63', 'description' => 'Tableau de bord, validations et tâches'],
        'Commercial' => ['icon' => 'clients.index', 'color' => '#F2705E', 'description' => 'Clients, devis, commandes et fidélité'],
        'Marketing' => ['icon' => 'followups.index', 'color' => '#53BDC5', 'description' => 'Relances, appels et WhatsApp'],
        'Caisse & boutique' => ['icon' => 'pos', 'color' => '#FFCE34', 'description' => 'Point de vente et encaissements'],
        'Production' => ['icon' => 'production.board', 'color' => '#134A63', 'description' => 'Graphisme, impression, machines, livraisons'],
        'Logistique' => ['icon' => 'inventory.index', 'color' => '#F59A3B', 'description' => 'Articles, stocks, achats et fournisseurs'],
        'Finance' => ['icon' => 'accounting.index', 'color' => '#FFCE34', 'description' => 'Factures, trésorerie, comptabilité'],
        'Ressources humaines' => ['icon' => 'hr.employees', 'color' => '#53BDC5', 'description' => 'Personnel, présence, paie, santé'],
        'Administration' => ['icon' => 'settings.company', 'color' => '#8B5A83', 'description' => 'Documents, rapports, audit, paramètres'],
    ];

    /**
     * Départements accessibles (sans « Mon espace ») avec leurs rubriques.
     *
     * @return list<array{heading: string, key: string, icon: string, color: string, description: string, items: list<array<string, string>>}>
     */
    public static function departments(User $user): array
    {
        return collect(self::for($user))
            ->reject(fn (array $group) => $group['heading'] === 'Mon espace')
            ->map(fn (array $group) => $group + ['key' => Str::slug($group['heading'])] + (self::DEPARTMENTS[$group['heading']] ?? ['icon' => 'launcher', 'color' => '#134A63', 'description' => '']))
            ->values()->all();
    }

    /**
     * Département et rubrique correspondant à la page affichée.
     *
     * @return array{department: array<string, mixed>|null, item: array<string, string>|null}
     */
    public static function current(User $user): array
    {
        foreach (self::departments($user) as $department) {
            foreach ($department['items'] as $item) {
                if (request()->routeIs($item['match'] ?? $item['route'])) {
                    return ['department' => $department, 'item' => $item];
                }
            }
        }

        return ['department' => null, 'item' => null];
    }

    /**
     * Pastilles de compteurs du menu (validations en attente, tâches ouvertes).
     *
     * @return array<string, int>
     */
    public static function badges(User $user): array
    {
        return once(fn () => [
            'approvals.index' => $user->can('approvals.view') ? app(ApprovalService::class)->pendingFor($user)->count() : 0,
            'tasks.index' => $user->can('tasks.view') ? Task::query()->where('assigned_to', $user->id)->where('status', '!=', 'done')->count() : 0,
        ]);
    }
}
