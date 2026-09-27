<?php

use App\Http\Controllers\AppointmentWhatsAppReminderController;
use App\Http\Controllers\CustomerPdfExportController;
use App\Http\Controllers\PlanningDataExportController;
use App\Http\Middleware\EnsureAppointmentsModuleEnabled;
use App\Http\Middleware\EnsureKitchenModuleEnabled;
use App\Http\Middleware\EnsureReservationsModuleEnabled;
use App\Http\Middleware\RedirectIfSeller;
use App\Livewire\Admin\Accounting\ChartOfAccounts;
use App\Livewire\Admin\Accounting\Expenses;
use App\Livewire\Admin\Accounting\GeneralLedger;
use App\Livewire\Admin\Accounting\JournalEntries;
use App\Livewire\Admin\Dashboard\Index;
use App\Livewire\Admin\Products\Form;
use App\Livewire\Admin\Reports\Dashboard;
use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Livewire\Kiosk\OrderScreen;
use App\Livewire\Kitchen\Display;
use App\Livewire\Pos\Terminal;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Order;
use App\Models\Table;
use App\Services\ReceiptCodeGenerator;
use App\Services\ReceiptPrinter;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Yeboox POS — Web Routes
|--------------------------------------------------------------------------
*/

// ── Public ──────────────────────────────
Route::get('/', function () {
    return redirect()->route('login');
});

// ── Authenticated Routes ──────────────────────────────
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    // Redirect /dashboard based on role (Sellers to POS terminal, others to Admin dashboard)
    Route::get('/dashboard', function () {
        if (auth()->user()->isSeller()) {
            return redirect()->route('pos.terminal');
        }

        return redirect()->route('admin.dashboard');
    })->name('dashboard');

    // ── Admin Portal (Protected against Sellers) ──────────────────────────────
    Route::prefix('admin')
        ->name('admin.')
        ->middleware([RedirectIfSeller::class, 'can:dashboard.view'])
        ->group(function () {

            // Dashboard
            Route::get('/', Index::class)->name('dashboard');

            // Products CRUD
            Route::get('/products', App\Livewire\Admin\Products\Index::class)->name('products.index');
            Route::get('/products/create', Form::class)->name('products.create');
            Route::get('/products/{productId}/edit', Form::class)->name('products.edit');

            // Categories CRUD
            Route::get('/categories', App\Livewire\Admin\Categories\Index::class)->name('categories.index');
            Route::get('/categories/create', App\Livewire\Admin\Categories\Form::class)->name('categories.create');
            Route::get('/categories/{categoryId}/edit', App\Livewire\Admin\Categories\Form::class)->name('categories.edit');

            // Customers CRUD
            Route::get('/customers', App\Livewire\Admin\Customers\Index::class)->name('customers.index');
            Route::get('/customers/create', App\Livewire\Admin\Customers\Form::class)->name('customers.create');
            Route::get('/customers/{customerId}/edit', App\Livewire\Admin\Customers\Form::class)->name('customers.edit');
            Route::get('/customers-export/pdf', CustomerPdfExportController::class)->name('customers.export.pdf');

            // Suppliers CRUD
            Route::get('/suppliers', App\Livewire\Admin\Suppliers\Index::class)->name('suppliers.index');
            Route::get('/suppliers/create', App\Livewire\Admin\Suppliers\Form::class)->name('suppliers.create');
            Route::get('/suppliers/{supplierId}/edit', App\Livewire\Admin\Suppliers\Form::class)->name('suppliers.edit');

            // Users
            Route::get('/users', App\Livewire\Admin\Users\Index::class)->name('users.index');

            // Centralized business settings
            Route::get('/settings', SettingsIndex::class)
                ->middleware('can:settings.view')
                ->name('settings.index');

            // Stocks
            Route::get('/stocks', App\Livewire\Admin\Stocks\Index::class)->name('stocks.index');

            // Tables (Floor Plan)
            Route::get('/tables', App\Livewire\Admin\Tables\Index::class)->name('tables.index');

            // Reservations
            Route::get('/reservations', App\Livewire\Admin\Reservations\Index::class)
                ->middleware(EnsureReservationsModuleEnabled::class)
                ->name('reservations.index');

            // Appointments
            Route::get('/appointments', App\Livewire\Admin\Appointments\Index::class)
                ->middleware([EnsureAppointmentsModuleEnabled::class, 'can:appointments.view'])
                ->name('appointments.index');
            Route::get('/appointments/{appointmentId}/whatsapp', AppointmentWhatsAppReminderController::class)
                ->middleware(EnsureAppointmentsModuleEnabled::class)
                ->name('appointments.whatsapp');
            Route::get('/planning-data/{dataset}/{format}', PlanningDataExportController::class)
                ->whereIn('dataset', ['appointments', 'reservations'])
                ->whereIn('format', ['xlsx', 'pdf', 'template'])
                ->name('planning-data.export');

            // Orders (Stage 3)
            Route::get('/orders', App\Livewire\Admin\Orders\Index::class)->name('orders.index');

            // Cash Sessions (Stage 3)
            Route::get('/cash-sessions', App\Livewire\Admin\CashSessions\Index::class)->name('cash-sessions.index');

            // Accounting (Stage 5 — OHADA)
            Route::get('/accounting/chart', ChartOfAccounts::class)->name('accounting.chart');
            Route::get('/accounting/journal', JournalEntries::class)->name('accounting.journal');
            Route::get('/accounting/ledger', GeneralLedger::class)->name('accounting.ledger');
            Route::get('/accounting/expenses', Expenses::class)->name('accounting.expenses');

            // Reports (Stage 6)
            Route::get('/reports', Dashboard::class)->name('reports.dashboard');

            // Receipt PDF & Printing (Stage 7)
            Route::get('/receipt/{order}', function (Order $order) {
                return (new ReceiptPrinter)->stream($order);
            })->name('receipt.stream');

            Route::get('/receipt/{order}/download', function (Order $order) {
                return (new ReceiptPrinter)->download($order);
            })->name('receipt.download');
        });

    // Receipt HTML Print (Accessible to all authenticated users)
    Route::get('/receipt/{order}/print', function (Order $order) {
        $order->load(['items.product', 'payments.paymentMethod', 'payments.currency', 'user', 'table', 'establishment.defaultCurrency']);

        $ticketSettings = app(ReceiptPrinter::class)->settingsFor($order->establishment);
        $codeImage = app(ReceiptCodeGenerator::class)->dataUri($ticketSettings['code_type'], $order->order_number);

        return view('receipts.ticket_print', [
            'order' => $order,
            'establishment' => $order->establishment,
            'items' => $order->items,
            'payments' => $order->payments,
            'generatedAt' => now(),
            'isAddition' => false,
            'ticketSettings' => $ticketSettings,
            'codeImage' => $codeImage,
        ]);
    })->name('receipt.print');

    // Addition HTML Print (Accessible to all authenticated users)
    Route::get('/pos/print-addition/{tableKey}', function ($tableKey) {
        $tablesCarts = session()->get('pos_tables_carts', []);
        $state = $tablesCarts[$tableKey] ?? null;
        if (! $state || empty($state['cart'])) {
            return 'Le panier est vide.';
        }

        $establishment = auth()->user()->establishment;
        abort_unless($establishment?->provisionalReceiptEnabled(), 403);

        $documentType = in_array(request('document'), ['addition', 'proforma'], true)
            ? request('document')
            : $establishment->getSetting('pos_provisional_receipt_type', 'addition');
        $currencyCode = in_array(request('currency'), $establishment->getSetting('enabled_currencies', ['CDF', 'USD']), true)
            ? request('currency')
            : 'CDF';

        $tableId = str_starts_with($tableKey, 'table_') ? substr($tableKey, 6) : null;
        $table = $tableId ? Table::where('establishment_id', $establishment->id)->find($tableId) : null;
        $ticketSettings = app(ReceiptPrinter::class)->settingsFor($establishment);

        $conversionRate = 1.0;
        if ($currencyCode === 'USD') {
            $usdId = Currency::where('code', 'USD')->value('id');
            $cdfId = Currency::where('code', 'CDF')->value('id');
            $conversionRate = (float) (ExchangeRate::query()
                ->where('establishment_id', $establishment->id)
                ->where('from_currency_id', $usdId)
                ->where('to_currency_id', $cdfId)
                ->whereDate('effective_date', '<=', today())
                ->latest('effective_date')->value('sell_rate') ?: 1);
        }

        $items = collect($state['cart'])->map(function ($item) use ($conversionRate) {
            return (object) [
                'product' => (object) ['name' => $item['name']],
                'quantity' => $item['qty'],
                'unit_price' => $item['price'] / $conversionRate,
                'total' => $item['total'] / $conversionRate,
            ];
        });

        // Calculate totals
        $subtotal = collect($state['cart'])->sum('total');
        $discountAmount = 0.0;
        if ($state['discountAmount'] > 0) {
            if ($state['discountType'] === 'percentage') {
                $discountAmount = $subtotal * ($state['discountAmount'] / 100);
            } else {
                $discountAmount = $state['discountAmount'];
            }
        }
        $total = max(0, $subtotal - $discountAmount);
        $subtotal /= $conversionRate;
        $discountAmount /= $conversionRate;
        $total /= $conversionRate;
        $orderNumber = ($documentType === 'addition' ? 'ADD-' : 'PRO-').now()->format('ymd').'-'.strtoupper(substr(uniqid(), -5));
        $codeImage = app(ReceiptCodeGenerator::class)->dataUri($ticketSettings['code_type'], $orderNumber);

        return view('receipts.ticket_print', [
            'establishment' => $establishment,
            'table' => $table,
            'items' => $items,
            'subtotal' => $subtotal,
            'discountAmount' => $discountAmount,
            'total' => $total,
            'generatedAt' => now(),
            'isAddition' => true,
            'provisionalDocumentType' => $documentType,
            'currencySymbol' => $currencyCode === 'CDF' ? 'FC' : $currencyCode,
            'currencyDecimals' => $currencyCode === 'CDF' ? 0 : 2,
            'displayExchangeRate' => $conversionRate,
            'orderNumber' => $orderNumber,
            'ticketSettings' => $ticketSettings,
            'codeImage' => $codeImage,
        ]);
    })->name('pos.print-addition');

    // ── POS Portal ──────────────────────────────
    Route::prefix('pos')->name('pos.')->middleware('can:pos.access')->group(function () {
        Route::get('/', Terminal::class)->name('terminal');
    });

    // ── Kitchen Display System ──────────────────────────────
    Route::prefix('kitchen')->name('kitchen.')->middleware([
        'can:kitchen.view',
        EnsureKitchenModuleEnabled::class,
    ])->group(function () {
        Route::get('/', Display::class)->name('display');
    });
});

// ── Kiosk (No Auth — Self-Service) ──────────────────────
Route::get('/kiosk', OrderScreen::class)->name('kiosk');

// ── Offline Fallback ────────────────────────────────────
Route::get('/offline', function () {
    return view('offline');
})->name('offline');
