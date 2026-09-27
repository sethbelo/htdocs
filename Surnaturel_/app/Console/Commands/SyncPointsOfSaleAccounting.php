<?php

namespace App\Console\Commands;

use App\Services\Accounting\AccountingSyncService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('erp:accounting-sync')]
#[Description('Vérifie que toutes les ventes et encaissements des points de vente sont comptabilisés, et passe les écritures manquantes')]
class SyncPointsOfSaleAccounting extends Command
{
    public function handle(AccountingSyncService $sync): int
    {
        $posted = $sync->sync();
        $this->info($posted ? "{$posted} écriture(s) manquante(s) passée(s)." : 'Comptabilité à jour : aucune écriture manquante.');

        return self::SUCCESS;
    }
}
