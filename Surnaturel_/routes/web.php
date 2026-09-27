<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\PrintController;
use App\Livewire;
use App\Support\HomeRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::livewire('/connexion', Livewire\Auth\Login::class)->name('login');
});

Route::post('/deconnexion', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware(['auth', 'session.timeout'])->group(function (): void {
    Route::livewire('/mot-de-passe', Livewire\Auth\ChangePassword::class)->name('password.change');

    Route::middleware('password.changed')->group(function (): void {
        Route::get('/', fn () => redirect()->to(HomeRoute::url(auth()->user())))->name('home');
        Route::livewire('/pin-caisse', Livewire\Auth\CashierPin::class)->name('profile.pin');
        Route::livewire('/tableau-de-bord', Livewire\Dashboard::class)->middleware('can:dashboard.view')->name('dashboard');
        Route::livewire('/validations', Livewire\Approvals\Inbox::class)->middleware('can:approvals.view')->name('approvals.index');
        Route::livewire('/taches', Livewire\Tasks\Index::class)->middleware('can:tasks.view')->name('tasks.index');
        Route::livewire('/direction/visa-paie', Livewire\Hr\PayrollApproval::class)->middleware('can:direction.payroll_sign')->name('direction.payroll');

        // Commercial
        Route::livewire('/clients', Livewire\Crm\Clients::class)->middleware('can:clients.view')->name('clients.index');
        Route::livewire('/clients/{client}', Livewire\Crm\ClientShow::class)->middleware('can:clients.view')->name('clients.show');
        Route::livewire('/pipeline', Livewire\Crm\Pipeline::class)->middleware('can:pipeline.view')->name('pipeline');
        Route::livewire('/fidelite', Livewire\Crm\Loyalty::class)->middleware('can:loyalty.view')->name('loyalty.index');
        Route::livewire('/marketing/suivi-clients', Livewire\Marketing\FollowUps::class)->middleware('can:followups.view')->name('followups.index');
        Route::livewire('/devis', Livewire\Sales\Quotes::class)->middleware('can:quotes.view')->name('quotes.index');
        Route::livewire('/commandes', Livewire\Orders\Index::class)->middleware('can:orders.view')->name('orders.index');
        Route::livewire('/commandes/nouvelle', Livewire\Orders\Create::class)->middleware('can:orders.create')->name('orders.create');
        Route::livewire('/commandes/{order}', Livewire\Orders\Show::class)->middleware('can:orders.view')->name('orders.show');

        // Caisse & boutique
        Route::livewire('/caisse', Livewire\Cash\OrderCheckout::class)->middleware('can:cash.collect')->name('cash.checkout');
        Route::livewire('/caisse/sessions', Livewire\Cash\Sessions::class)->middleware('can:cash.view')->name('cash.sessions');
        Route::livewire('/pos', Livewire\Pos\Terminal::class)->middleware('can:pos.create')->name('pos');

        // Production
        Route::livewire('/graphisme', Livewire\Design\Board::class)->middleware('can:design.view')->name('design.board');
        Route::livewire('/graphisme/cahier/{designJob}', Livewire\Design\Notebook::class)->middleware('can:design.view')->name('design.notebook');
        Route::livewire('/impression', Livewire\Production\Board::class)->middleware('can:production.view')->name('production.board');
        Route::livewire('/machines', Livewire\Production\Machines::class)->middleware('can:machines.view')->name('machines.index');
        Route::livewire('/livraisons', Livewire\Deliveries\Index::class)->middleware('can:deliveries.view')->name('deliveries.index');

        // Logistique
        Route::livewire('/articles', Livewire\Inventory\Products::class)->middleware('can:products.view')->name('products.index');
        Route::livewire('/stocks', Livewire\Inventory\Stock::class)->middleware('can:inventory.view')->name('inventory.index');
        Route::livewire('/stocks/entrees-sorties', Livewire\Inventory\Operations::class)->middleware('can:inventory.view')->name('inventory.operations');
        Route::livewire('/stocks/transferts', Livewire\Inventory\Transfers::class)->middleware('can:inventory.view')->name('inventory.transfers');
        Route::livewire('/stocks/rapport-temps-reel', Livewire\Inventory\LiveReport::class)->middleware('can:inventory.view')->name('inventory.report');
        Route::livewire('/achats', Livewire\Purchasing\Index::class)->middleware('can:purchases.view')->name('purchases.index');
        Route::livewire('/fournisseurs', Livewire\Purchasing\Suppliers::class)->middleware('can:suppliers.view')->name('suppliers.index');

        // Finance
        Route::livewire('/factures', Livewire\Finance\Invoices::class)->middleware('can:invoices.view')->name('invoices.index');
        Route::livewire('/tresorerie', Livewire\Finance\Treasury::class)->middleware('can:treasury.view')->name('treasury.index');
        Route::livewire('/comptabilite', Livewire\Finance\Accounting::class)->middleware('can:accounting.view')->name('accounting.index');
        Route::livewire('/notes-de-frais', Livewire\Finance\Expenses::class)->middleware('can:expenses.view')->name('expenses.index');
        Route::livewire('/bons', Livewire\Finance\Vouchers::class)->middleware('can:vouchers.view')->name('vouchers.index');

        // RH
        Route::livewire('/rh/employes', Livewire\Hr\Employees::class)->middleware('can:employees.view')->name('hr.employees');
        Route::livewire('/rh/departements', Livewire\Hr\Departments::class)->middleware('can:departments.view')->name('hr.departments');
        Route::livewire('/rh/presence', Livewire\Hr\Attendance::class)->middleware('can:attendance.view')->name('hr.attendance');
        Route::livewire('/rh/conges', Livewire\Hr\Leaves::class)->middleware('can:leaves.view')->name('hr.leaves');
        Route::livewire('/rh/paie', Livewire\Hr\Payroll::class)->middleware('can:payroll.view')->name('hr.payroll');
        Route::livewire('/rh/badges-cartes', Livewire\Hr\Cards::class)->middleware('can:cards.view')->name('hr.cards');
        Route::livewire('/rh/sante-assurance', Livewire\Hr\Affiliations::class)->middleware('can:affiliations.view')->name('hr.affiliations');

        // Administration
        Route::livewire('/documents', Livewire\Documents\Index::class)->middleware('can:documents.view')->name('documents.index');
        Route::livewire('/rapports', Livewire\Reports\Index::class)->middleware('can:reports.view')->name('reports.index');
        Route::livewire('/audit', Livewire\Audit\Index::class)->middleware('can:audit.view')->name('audit.index');
        Route::livewire('/parametres/entreprise', Livewire\Settings\Company::class)->middleware('can:settings.view')->name('settings.company');
        Route::livewire('/parametres/sieges', Livewire\Settings\Sites::class)->middleware('can:sites.view')->name('settings.sites');
        Route::livewire('/parametres/utilisateurs', Livewire\Settings\Users::class)->middleware('can:users.view')->name('settings.users');
        Route::livewire('/parametres/roles', Livewire\Settings\Roles::class)->middleware('can:roles.view')->name('settings.roles');
        Route::livewire('/parametres/validations', Livewire\Settings\Workflows::class)->middleware('can:approvals.update')->name('settings.workflows');
        Route::livewire('/parametres/documents', Livewire\Settings\Documents::class)->middleware('can:settings.update')->name('settings.documents');

        // Impressions (PDF : A4, A5, ticket thermique 80 mm)
        Route::controller(PrintController::class)->prefix('imprimer')->name('print.')->group(function (): void {
            Route::get('/commande/{order}/ticket', 'orderTicket')->name('order-ticket');
            Route::get('/devis/{quote}', 'quote')->name('quote');
            Route::get('/facture/{invoice}/{format?}', 'invoice')->whereIn('format', ['a4', 'a5', 'ticket'])->name('invoice');
            Route::get('/vente/{sale}/ticket', 'saleReceipt')->name('sale-receipt');
            Route::get('/client/{client}/carte-fidelite', 'loyaltyCard')->name('loyalty-card');
            Route::get('/bat/{proof}', 'proof')->name('proof');
            Route::get('/livraison/{delivery}', 'deliveryNote')->name('delivery-note');
            Route::get('/session/{cashSession}', 'cashSession')->name('cash-session');
            Route::get('/bulletin/{payslip}', 'payslip')->name('payslip');
            Route::get('/bon/{voucher}', 'voucher')->name('voucher');
            Route::get('/magasin/{stockOperation}', 'stockOperation')->name('stock-operation');
            Route::get('/transfert/{stockTransfer}', 'stockTransfer')->name('stock-transfer');
            Route::get('/fiche-impression/{designJob}', 'designBrief')->name('design-brief');
            Route::get('/affiliation/{affiliation}', 'affiliation')->name('affiliation');
            Route::get('/affiliation/{affiliation}/carte', 'memberCard')->name('member-card');
            Route::get('/prise-en-charge/{careVoucher}', 'careVoucher')->name('care-voucher');
        });

        // Fichiers privés (accès contrôlé ou lien signé)
        Route::get('/fichiers/commande/{orderFile}', [FileController::class, 'orderFile'])->name('files.order');
        Route::get('/fichiers/conception/{designVersion}', [FileController::class, 'designVersion'])->name('files.design');
        Route::get('/fichiers/bat/{proof}', [FileController::class, 'lockedProof'])->name('files.proof');
    });
});

// Vérification publique d'une carte de service (QR code au verso) : lien signé, infalsifiable.
Route::get('/verification/agent/{employee}', [PrintController::class, 'verifyEmployee'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('employees.verify');

Route::get('/documents/telecharger/{version}', [FileController::class, 'document'])
    ->middleware(['auth', 'signed'])
    ->name('documents.download');
