<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectronReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_run_page_renders_without_an_internal_server_error(): void
    {
        $this->get('/setup')
            ->assertOk()
            ->assertSee('Votre commerce. Un nouveau départ.')
            ->assertSee('Continuer');
    }

    public function test_electron_readiness_endpoint_checks_sqlite_and_session_middleware(): void
    {
        $this->getJson('/ready')
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }

    public function test_windows_runtime_includes_php_and_uses_sqlite_without_postgresql(): void
    {
        $main = file_get_contents(base_path('main.cjs'));
        $package = json_decode(file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('9.0.0', $package['version']);
        $this->assertStringContainsString("DB_CONNECTION: 'sqlite'", $main);
        $this->assertStringNotContainsString('startPostgresCluster', $main);
        $this->assertContains('php', array_column($package['build']['extraResources'], 'to'));
        $this->assertNotContains('postgres', array_column($package['build']['extraResources'], 'to'));
        $this->assertContains('!.env*', $package['build']['files']);
        $this->assertStringContainsString('APP_KEY: applicationKey', $main);
        foreach (['php.exe', 'libsqlite3.dll', 'vcruntime140.dll', 'vcruntime140_1.dll', 'msvcp140.dll', 'ext/php_pdo_sqlite.dll'] as $file) {
            $this->assertFileExists(base_path('php-win/'.$file));
        }
    }

    public function test_professional_500_page_is_renderable(): void
    {
        $html = view('errors.500')->render();
        $this->assertStringContainsString('Un incident temporaire est survenu.', $html);
        $this->assertStringContainsString('Réessayer', $html);
    }

    public function test_sqlite_uses_a_busy_timeout_for_windows_file_locks(): void
    {
        $this->assertGreaterThanOrEqual(5000, config('database.connections.sqlite.busy_timeout'));
        $this->assertTrue(config('database.connections.sqlite.foreign_key_constraints'));
    }

    public function test_receipt_printing_uses_an_isolated_thermal_document(): void
    {
        $main = file_get_contents(base_path('main.cjs'));
        $this->assertStringContainsString('yeboox-receipt-sheet', $main);
        $this->assertStringContainsString('standaloneDocument', $main);
        $this->assertStringContainsString('printWindow.webContents.print', $main);
    }

    public function test_electron_runtime_blocks_external_network_dependencies(): void
    {
        $main = file_get_contents(base_path('main.cjs'));
        $this->assertStringContainsString('onBeforeRequest', $main);
        $this->assertStringContainsString("'127.0.0.1', 'localhost', '::1'", $main);
    }

    public function test_pos_waits_for_the_receipt_and_prints_each_sale_only_once(): void
    {
        $view = file_get_contents(resource_path('views/layouts/pos.blade.php'));
        $this->assertStringContainsString('printReceiptWhenReady', $view);
        $this->assertStringContainsString('document.fonts.ready', $view);
        $this->assertStringContainsString('lastAutomaticallyPrintedSale === saleId', $view);
    }

    public function test_electron_verification_includes_a_complete_sqlite_migration_cycle(): void
    {
        $script = file_get_contents(base_path('scripts/verify-electron-runtime.cjs'));
        foreach (['migrate:fresh', 'migrate:rollback', 'migrationCheckDatabase'] as $check) {
            $this->assertStringContainsString($check, $script);
        }
    }

    public function test_electron_reports_local_process_failures_before_showing_the_application(): void
    {
        $main = file_get_contents(base_path('main.cjs'));
        foreach (['validateBundledPhpRuntime', 'frenchPhpStartupReason', 'stopAfterStartupFailure', "phpServer.on('exit'"] as $check) {
            $this->assertStringContainsString($check, $main);
        }
    }

    public function test_installer_checks_the_included_runtime_before_completion(): void
    {
        $installer = file_get_contents(base_path('build/installer.nsh'));
        $this->assertStringContainsString('!macro customInstall', $installer);
        $this->assertStringContainsString('runtime-check.php', $installer);
        $this->assertFileExists(base_path('php-win/runtime-check.php'));
    }
}
