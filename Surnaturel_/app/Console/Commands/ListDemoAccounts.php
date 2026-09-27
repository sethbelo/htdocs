<?php

namespace App\Console\Commands;

use App\Support\DemoAccounts;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('erp:demo-accounts')]
#[Description('Affiche les comptes de démonstration (identifiants, mode de connexion, espace d’accueil)')]
class ListDemoAccounts extends Command
{
    public function handle(): int
    {
        $this->table(
            ['Rôle', 'Connexion', 'Identifiant / badge', 'Secret', 'Espace d’accueil', 'Siège'],
            collect(DemoAccounts::all())->map(fn (array $account): array => [
                $account['label'],
                $account['mode']->shortLabel(),
                $account['badge'] ?? $account['username'],
                $account['mode']->value === 'password' ? 'mot de passe : '.DemoAccounts::PASSWORD : 'PIN : '.DemoAccounts::PIN,
                $account['home'],
                $account['site'],
            ])->all(),
        );

        return self::SUCCESS;
    }
}
