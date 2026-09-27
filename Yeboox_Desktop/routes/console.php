<?php

use Dompdf\Dompdf;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('yeboox:health', function () {
    // Ces tables couvrent tous les modules affichés après authentification.
    // Une base partiellement migrée est refusée avant le chargement de l'UI,
    // ce qui évite un écran 500 tardif dans Electron.
    $requiredTables = [
        'users', 'settings', 'roles', 'permissions', 'model_has_roles',
        'customers', 'subscriptions', 'products', 'product_categories',
        'unit_of_measures', 'product_combo_items', 'stock_movements',
        'appointments', 'register_sessions', 'sales', 'sale_items',
        'sale_payments', 'payment_methods', 'invoices', 'invoice_items',
        'invoice_payments', 'credit_notes', 'accounting_records', 'expenses',
        'purchases', 'disbursement_requests', 'equity_movements',
        'companies', 'establishments', 'points_of_sale', 'pos_subscriptions',
        'sync_outbox', 'pos_sync_credentials', 'sync_receipts',
        'jobs', 'job_batches', 'failed_jobs',
    ];
    $missingTables = collect($requiredTables)->reject(fn ($table) => Schema::hasTable($table))->values();
    $driver = DB::connection()->getDriverName();
    $integrityValue = 'ok';
    $foreignKeyViolations = collect();

    if ($driver === 'sqlite') {
        $integrity = DB::selectOne('PRAGMA quick_check');
        $integrityValue = $integrity ? array_values((array) $integrity)[0] : null;
        $foreignKeyViolations = collect(DB::select('PRAGMA foreign_key_check'));
    } else {
        // Une requête réelle vérifie la connexion principale PostgreSQL sans
        // exécuter de commande PRAGMA propre à SQLite.
        DB::selectOne('SELECT 1 AS healthy');
    }
    $requiredPaths = [
        storage_path('framework/views'),
        storage_path('framework/cache/data'),
        storage_path('framework/sessions'),
        storage_path('logs'),
    ];
    $unwritablePaths = collect($requiredPaths)->reject(fn ($path) => is_dir($path) && is_writable($path))->values();
    $databaseExtension = $driver === 'pgsql' ? 'pdo_pgsql' : 'pdo_sqlite';
    $missingExtensions = collect([$databaseExtension, 'dom', 'mbstring', 'gd', 'fileinfo', 'zip', 'simplexml', 'xmlreader', 'xmlwriter'])
        ->reject(fn ($extension) => extension_loaded($extension))
        ->values();
    $pdfEngineAvailable = class_exists(Dompdf::class);

    if ($missingTables->isNotEmpty() || $integrityValue !== 'ok' || $foreignKeyViolations->isNotEmpty() || $unwritablePaths->isNotEmpty() || $missingExtensions->isNotEmpty() || ! $pdfEngineAvailable) {
        $this->error(json_encode([
            'sqlite' => $integrityValue,
            'database_driver' => $driver,
            'foreign_key_violations' => $foreignKeyViolations->count(),
            'missing_tables' => $missingTables,
            'unwritable_paths' => $unwritablePaths,
            'missing_extensions' => $missingExtensions,
            'pdf_engine' => $pdfEngineAvailable,
        ], JSON_UNESCAPED_SLASHES));

        return 1;
    }

    $this->info(json_encode([
        'status' => 'ok',
        'php' => PHP_VERSION,
        'database_driver' => $driver,
        'database' => DB::connection()->getDatabaseName(),
        'integrity' => $integrityValue,
        'foreign_key_violations' => 0,
        'tables' => count($requiredTables),
        'pdf' => 'dompdf',
    ]));

    return 0;
})->purpose('Valider PHP, les fichiers inscriptibles et la base principale active');
