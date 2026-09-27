<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncStatusCommand extends Command
{
    protected $signature = 'sync:status';

    protected $description = 'Contrôler SQLite, PostgreSQL et la file de ventes hors ligne';

    public function handle(): int
    {
        $default = DB::getDefaultConnection();
        $defaultDriver = DB::connection($default)->getDriverName();
        $localConnection = $defaultDriver === 'sqlite' ? $default : 'offline';
        $centralConnection = $defaultDriver === 'pgsql' ? $default : 'central';

        $local = [
            'connection' => $localConnection,
            'driver' => DB::connection($localConnection)->getDriverName(),
            'pending' => DB::connection($localConnection)->table('sync_outbox')->whereIn('status', ['pending', 'retry'])->count(),
            'synced' => DB::connection($localConnection)->table('sync_outbox')->where('status', 'synced')->count(),
        ];

        try {
            $central = [
                'connection' => $centralConnection,
                'driver' => DB::connection($centralConnection)->getDriverName(),
                'database' => DB::connection($centralConnection)->getDatabaseName(),
                'sales' => DB::connection($centralConnection)->table('sales')->count(),
                'receipts' => DB::connection($centralConnection)->table('sync_receipts')->count(),
                'terminals' => DB::connection($centralConnection)->table('pos_sync_credentials')->where('is_active', true)->count(),
            ];
        } catch (\Throwable $exception) {
            $this->error('PostgreSQL central est injoignable : '.$exception->getMessage());
            $this->line(json_encode(['local' => $local], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return self::FAILURE;
        }

        $this->info(json_encode([
            'status' => 'ok',
            'primary' => ['connection' => $default, 'driver' => $defaultDriver],
            'local' => $local,
            'central' => $central,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
