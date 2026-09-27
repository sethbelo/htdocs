<?php

use App\Http\Controllers\CurrencyPreferenceController;
use App\Http\Controllers\InventoryPriceGridPdfController;
use App\Http\Controllers\InventoryStockPdfController;
use App\Http\Controllers\ManagementReportPdfController;
use App\Http\Controllers\PointOfSalePreferenceController;
use App\Http\Controllers\SalesPdfController;
use App\Livewire\Accounting;
use App\Livewire\Admin;
use App\Livewire\Appointments;
use App\Livewire\Customers;
use App\Livewire\Dashboard;
use App\Livewire\FirstRunSetup;
use App\Livewire\Inventory;
use App\Livewire\Invoices;
use App\Livewire\MasterCompanies;
use App\Livewire\Operations;
use App\Livewire\Pos;
use App\Livewire\Reports;
use App\Livewire\Sales;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/ready', function () {
    $integrity = DB::connection()->getDriverName() === 'sqlite'
        ? DB::selectOne('PRAGMA quick_check')
        : DB::selectOne("SELECT 'ok' AS integrity");
    $integrityValue = $integrity ? array_values((array) $integrity)[0] : null;

    abort_unless(
        $integrityValue === 'ok' && Schema::hasTable('users') && Schema::hasTable('settings'),
        503,
    );

    return response()->json(['status' => 'ok']);
})->name('ready');

Route::get('/', function () {
    if (app()->environment('testing')) {
        return redirect()->route('login');
    }

    $installed = Schema::hasTable('users')
        && Schema::hasTable('settings')
        && User::query()->exists()
        && Setting::get('setup_completed');

    return redirect()->route($installed ? 'login' : 'setup');
});

Route::get('/setup', FirstRunSetup::class)->name('setup');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->post('/preferences/point-of-sale', PointOfSalePreferenceController::class)->name('preferences.point-of-sale');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'master.admin',
])->get('/master/companies', MasterCompanies::class)->name('master.companies');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'tenant',
])->group(function () {
    Route::get('/dashboard', Dashboard::class)->middleware('can:view dashboard')->name('dashboard');
    Route::post('/preferences/currency', CurrencyPreferenceController::class)->name('preferences.currency');

    Route::get('/pos', Pos::class)->middleware(['pos.subscription', 'can:manage POS'])->name('pos');
    Route::get('/appointments', Appointments::class)->middleware(['pos.subscription', 'can:manage appointments'])->name('appointments');
    Route::get('/customers', Customers::class)->middleware(['pos.subscription', 'can:manage customers'])->name('customers');

    Route::get('/inventory', Inventory::class)->middleware(['pos.subscription', 'can:manage inventory'])->name('inventory');
    Route::get('/inventory/export/stock.pdf', InventoryStockPdfController::class)->middleware(['pos.subscription', 'can:export inventory'])->name('inventory.stock.pdf');
    Route::get('/inventory/export/price-grid.pdf', InventoryPriceGridPdfController::class)->middleware(['pos.subscription', 'can:export inventory'])->name('inventory.price-grid.pdf');
    Route::get('/invoices', Invoices::class)->middleware(['pos.subscription', 'can:manage invoices'])->name('invoices');
    Route::get('/accounting', Accounting::class)->middleware(['pos.subscription', 'can:manage accounting'])->name('accounting');
    Route::get('/reports', Reports::class)->middleware(['pos.subscription', 'can:manage reports'])->name('reports');
    Route::get('/reports/export/pdf', ManagementReportPdfController::class)->middleware(['pos.subscription', 'can:export reports'])->name('reports.pdf');
    Route::get('/sales', Sales::class)->middleware(['pos.subscription', 'can:manage sales'])->name('sales');
    Route::get('/sales/export/pdf', SalesPdfController::class)->middleware(['pos.subscription', 'can:export sales'])->name('sales.pdf');

    Route::middleware(['pos.subscription', 'can:manage operations'])->group(function () {
        Route::get('/expenses', Operations::class)->defaults('module', 'expenses')->name('expenses');
        Route::get('/purchases', Operations::class)->defaults('module', 'purchases')->name('purchases');
        Route::get('/disbursements', Operations::class)->defaults('module', 'disbursements')->name('disbursements');
        Route::get('/equity', Operations::class)->defaults('module', 'equity')->name('equity');
        Route::get('/planning', Operations::class)->defaults('module', 'planning')->name('planning');
    });

    Route::get('/admin', Admin::class)->middleware('can:manage administration')->name('admin');
});
