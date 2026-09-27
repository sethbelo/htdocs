<?php

namespace App\Console\Commands;

use App\Models\RegisterSession;
use App\Models\Sale;
use App\Services\Sync\OfflineSalePublisher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackfillSaleSyncCommand extends Command
{
    protected $signature = 'sync:backfill-sales';

    protected $description = 'Ajouter les ventes locales historiques à la file de synchronisation';

    public function handle(OfflineSalePublisher $publisher): int
    {
        RegisterSession::query()->whereNull('sync_uuid')->eachById(
            fn (RegisterSession $session) => $session->forceFill(['sync_uuid' => (string) Str::uuid()])->saveQuietly()
        );

        $count = 0;
        Sale::query()->where(fn ($query) => $query->whereNull('synced_at')->orWhere('sync_status', '!=', 'synced'))
            ->eachById(function (Sale $sale) use ($publisher, &$count): void {
                DB::transaction(function () use ($sale, $publisher, &$count): void {
                    $publisher->publish($sale);
                    $count++;
                });
            });

        $this->info("{$count} vente(s) prête(s) pour la synchronisation.");

        return self::SUCCESS;
    }
}
