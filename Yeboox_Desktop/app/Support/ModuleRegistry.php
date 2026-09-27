<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\Carbon;

final class ModuleRegistry
{
    public const DEFINITIONS = [
        'expenses' => ['label' => 'Dépenses', 'icon' => 'receipt-percent', 'description' => 'Notes de frais et charges OHADA'],
        'purchases' => ['label' => 'Achats', 'icon' => 'shopping-bag', 'description' => 'Commandes et factures fournisseurs'],
        'disbursements' => ['label' => 'Décaissements', 'icon' => 'hand-raised', 'description' => 'Demandes, validations et paiements'],
        'equity' => ['label' => 'Capitaux propres', 'icon' => 'building-library', 'description' => 'Apports et retraits des associés'],
        'planning' => ['label' => 'Planning', 'icon' => 'calendar-days', 'description' => 'Planning mensuel des rendez-vous'],
        'loyalty' => ['label' => 'Carte de fidélité', 'icon' => 'gift', 'description' => 'Récompenses par points ou prestations', 'default_enabled' => false],
    ];

    public static function configuration(): array
    {
        $stored = json_decode((string) Setting::get('monthly_modules', '{}'), true) ?: [];
        $defaultExpiry = now()->endOfMonth()->toDateString();

        return collect(self::DEFINITIONS)->mapWithKeys(fn ($meta, $key) => [$key => [
            'enabled' => (bool) ($stored[$key]['enabled'] ?? ($meta['default_enabled'] ?? true)),
            'expires_at' => $stored[$key]['expires_at'] ?? $defaultExpiry,
        ]])->all();
    }

    public static function enabled(string $module): bool
    {
        $config = self::configuration()[$module] ?? null;

        return $config && $config['enabled'] && Carbon::parse($config['expires_at'])->endOfDay()->isFuture();
    }
}
