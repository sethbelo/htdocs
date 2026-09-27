<?php

namespace App\Support;

/**
 * Comptes de démonstration (environnement local uniquement) : un compte par rôle, connexion par identifiant et mot de passe.
 */
class DemoAccounts
{
    public const PASSWORD = 'password';

    public const PIN = '1234';

    public static function username(string $role): string
    {
        return str_replace('-', '.', $role);
    }

    public static function badge(string $role): string
    {
        return 'BADGE-'.strtoupper(str_replace('-', '', $role));
    }

    /**
     * @return list<array{role: string, label: string, username: string, email: string, home: string, site: string}>
     */
    public static function all(): array
    {
        $shopRoles = ['caissier', 'responsable-boutique', 'gerant'];
        $accounts = [];

        foreach (config('erp.roles') as $role => $definition) {
            $accounts[] = [
                'role' => $role,
                'label' => $definition['label'],
                'username' => self::username($role),
                'email' => self::username($role).'@demo.test',
                'home' => $definition['home'] ?? 'home',
                'site' => in_array($role, $shopRoles, true) ? 'Boutique Limete' : 'Siège Kinshasa',
            ];
        }

        $accounts[] = ['role' => 'caissier', 'label' => 'Caissier (caisse commandes du siège)', 'username' => 'caissier.siege', 'email' => 'caissier.siege@demo.test', 'home' => 'pos', 'site' => 'Siège Kinshasa'];

        return $accounts;
    }

    public static function enabled(): bool
    {
        return app()->isLocal() && (bool) config('erp.demo_accounts', true);
    }
}
