<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BootstrapCentralDatabaseCommand extends Command
{
    protected $signature = 'central:bootstrap {--source= : Connexion SQLite source} {--force : Remplacer les données centrales existantes}';

    protected $description = 'Initialiser PostgreSQL avec les données métier de la base SQLite locale';

    /** Ordre respectant toutes les clés étrangères PostgreSQL du projet. */
    private const TABLES = [
        'companies', 'establishments', 'points_of_sale', 'pos_subscriptions',
        'users', 'roles', 'permissions', 'role_has_permissions',
        'model_has_roles', 'model_has_permissions', 'point_of_sale_user',
        'personal_access_tokens', 'passkeys', 'unit_of_measures',
        'product_categories', 'products', 'product_combo_items', 'customers',
        'subscriptions', 'payment_methods', 'register_sessions', 'invoices',
        'invoice_items', 'invoice_payments', 'sales', 'sale_items',
        'sale_payments', 'credit_notes', 'appointments', 'stock_movements',
        'accounting_records', 'expenses', 'purchases', 'disbursement_requests',
        'equity_movements', 'activity_logs', 'settings',
    ];

    public function handle(): int
    {
        $source = (string) ($this->option('source') ?: (DB::connection()->getDriverName() === 'sqlite' ? DB::getDefaultConnection() : 'offline'));

        if (DB::connection($source)->getDriverName() !== 'sqlite') {
            $this->error('Cette commande doit être lancée depuis l’édition locale SQLite.');

            return self::FAILURE;
        }

        try {
            DB::connection('central')->getPdo();
        } catch (\Throwable $exception) {
            $this->error('Connexion PostgreSQL impossible : '.$exception->getMessage());

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Remplacer les données métier de PostgreSQL par la copie locale ?')) {
            return self::SUCCESS;
        }

        $tables = collect(self::TABLES)
            ->filter(fn (string $table) => Schema::connection($source)->hasTable($table) && Schema::connection('central')->hasTable($table))
            ->values();

        $quoted = $tables->map(fn (string $table) => '"'.str_replace('"', '""', $table).'"')->implode(', ');
        DB::connection('central')->statement("TRUNCATE TABLE {$quoted} RESTART IDENTITY CASCADE");

        foreach ($tables as $table) {
            $booleanColumns = collect(DB::connection('central')->select(
                "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ? AND data_type = 'boolean'",
                [$table],
            ))->pluck('column_name')->all();

            $copied = 0;
            DB::connection($source)->table($table)->orderByRaw('1')->chunk(250, function ($rows) use ($table, $booleanColumns, &$copied): void {
                $batch = $rows->map(function ($row) use ($booleanColumns): array {
                    $values = (array) $row;
                    foreach ($booleanColumns as $column) {
                        if (array_key_exists($column, $values) && $values[$column] !== null) {
                            $values[$column] = (bool) $values[$column];
                        }
                    }

                    return $values;
                })->all();
                if ($batch !== []) {
                    DB::connection('central')->table($table)->insert($batch);
                    $copied += count($batch);
                }
            });

            if (Schema::connection('central')->hasColumn($table, 'id')) {
                $safe = '"'.str_replace('"', '""', $table).'"';
                DB::connection('central')->statement(
                    "SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE(MAX(id), 1), MAX(id) IS NOT NULL) FROM {$safe}"
                );
            }
            $this->line("{$table} : {$copied} ligne(s)");
        }

        $token = (string) config('offline-sync.token');
        if ($token !== '') {
            $points = DB::connection('central')->table('points_of_sale')->where('is_active', true)->get();
            foreach ($points as $point) {
                DB::connection('central')->table('pos_sync_credentials')->updateOrInsert(
                    ['point_of_sale_id' => $point->id, 'name' => 'Terminal Desktop'],
                    [
                        'company_id' => $point->company_id,
                        'token_hash' => hash('sha256', $token),
                        'token_hint' => substr($token, -8),
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }

        $this->info('PostgreSQL est initialisé et les terminaux sont autorisés.');

        return self::SUCCESS;
    }
}
