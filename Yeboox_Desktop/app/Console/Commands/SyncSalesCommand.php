<?php

namespace App\Console\Commands;

use App\Services\Sync\SaleOutboxSynchronizer;
use Illuminate\Console\Command;

class SyncSalesCommand extends Command
{
    protected $signature = 'sync:sales {--limit=} {--watch : Rester actif et reprendre automatiquement}';

    protected $description = 'Synchroniser les ventes SQLite vers le serveur PostgreSQL central';

    public function handle(SaleOutboxSynchronizer $synchronizer): int
    {
        do {
            $stats = $synchronizer->syncPending($this->option('limit') ? (int) $this->option('limit') : null);
            if (! $this->option('watch')) {
                $this->info(sprintf(
                    '%d vente(s) traitée(s), %d synchronisée(s), %d en attente.',
                    $stats['processed'], $stats['synced'], $stats['pending'],
                ));
                break;
            }

            sleep(max(5, (int) config('offline-sync.interval_seconds', 15)));
        } while (true);

        return self::SUCCESS;
    }
}
