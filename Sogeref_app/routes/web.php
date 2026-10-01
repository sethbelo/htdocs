<?php

/*
|--------------------------------------------------------------------------
| SOGEREF ERP — Routes web
|--------------------------------------------------------------------------
| Chaque module est protégé par le middleware « module:{clé} » qui vérifie
| à la fois l'activation du module (Master système) et le droit « {clé}.view ».
*/

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\PersonalExportController;
use App\Livewire;
use App\Models\MessageAttachment;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// ── Fichiers publics en mode Desktop (Electron) ─────────────────────────────
if (env('SOGEREF_DESKTOP')) {
    Route::get('/storage/{path}', function (string $path) {
        $root = realpath(storage_path('app/public'));
        $file = realpath(storage_path('app/public/'.$path));
        abort_unless($root && $file && str_starts_with(strtolower($file), strtolower($root.DIRECTORY_SEPARATOR)) && is_file($file), 404);

        return response()->file($file);
    })->where('path', '.*')->name('desktop.storage');
}

// ── Authentification ────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::view('/connexion', 'auth.login')->name('login');
    Route::post('/connexion', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
});
Route::post('/deconnexion', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function () {

    // ── Espace personnel (tous les employés) ────────────────────────────────
    Route::middleware('module:personal')->prefix('espace-personnel')->name('personal')->group(function () {
        Route::get('/', Livewire\Personal\Overview::class)->name('');
        Route::get('/notes', Livewire\Personal\Notes::class)->name('.notes');
        Route::get('/tableurs', Livewire\Personal\Sheets::class)->name('.sheets');
        Route::get('/bons', Livewire\Personal\Vouchers::class)->name('.vouchers');
        Route::get('/conges', Livewire\Personal\Leaves::class)->name('.leaves');
        Route::get('/taches', Livewire\Personal\Tasks::class)->name('.tasks');

        Route::controller(PersonalExportController::class)->group(function () {
            Route::get('/notes/{note}/imprimer', 'notePrint')->name('.notes.print');
            Route::get('/notes/{note}/pdf', 'notePdf')->name('.notes.pdf');
            Route::get('/tableurs/{sheet}/imprimer', 'sheetPrint')->name('.sheets.print');
            Route::get('/tableurs/{sheet}/pdf', 'sheetPdf')->name('.sheets.pdf');
            Route::get('/tableurs/{sheet}/excel', 'sheetExcel')->name('.sheets.xlsx');
            Route::get('/bons/{voucher}/pdf', 'voucherPdf')->name('.vouchers.pdf');
            Route::get('/taches/imprimer', 'tasksPrint')->name('.tasks.print');
        });
    });

    // ── Pilotage ────────────────────────────────────────────────────────────
    // Lanceur d'applications (page d'accueil) puis tableau de bord du Pilotage.
    Route::get('/', Livewire\Launcher::class)->name('launcher');
    Route::get('/tableau-de-bord', Livewire\Dashboard::class)->name('dashboard');
    Route::middleware('module:orders')->group(function () {
        Route::get('/commandes', Livewire\Orders\Index::class)->name('orders.index');
        Route::get('/commandes/{order}', Livewire\Orders\Show::class)->name('orders.show');
    });

    // ── Réception & CRM ─────────────────────────────────────────────────────
    Route::get('/reception', Livewire\Reception\Desk::class)->middleware('module:reception')->name('reception');
    Route::get('/crm', Livewire\Crm\Pipeline::class)->middleware('module:crm')->name('crm');
    Route::middleware('module:contacts')->group(function () {
        Route::get('/contacts', Livewire\Crm\Contacts::class)->name('contacts');
        Route::get('/contacts/{contact}', Livewire\Crm\ContactShow::class)->name('contacts.show');
    });
    Route::get('/fidelite', Livewire\Crm\Loyalty::class)->middleware('module:loyalty')->name('loyalty');

    // ── Ventes : devis, factures, point de vente, location ─────────────────
    Route::middleware('module:quotes')->group(function () {
        Route::get('/devis', Livewire\Sales\Documents::class)->defaults('type', 'quote')->name('quotes');
        Route::get('/devis/nouveau', Livewire\Sales\DocumentEditor::class)->defaults('type', 'quote')->name('quotes.create');
    });
    Route::middleware('module:invoices')->group(function () {
        Route::get('/factures', Livewire\Sales\Documents::class)->defaults('type', 'invoice')->name('invoices');
        Route::get('/factures/nouvelle', Livewire\Sales\DocumentEditor::class)->defaults('type', 'invoice')->name('invoices.create');
    });
    Route::get('/documents/{document}', Livewire\Sales\DocumentShow::class)->name('documents.show');
    Route::get('/documents/{document}/modifier', Livewire\Sales\DocumentEditor::class)->name('documents.edit');
    Route::get('/point-de-vente', Livewire\Sales\Pos::class)->middleware('module:pos')->name('pos');
    Route::get('/location', Livewire\Sales\Rentals::class)->middleware('module:rentals')->name('rentals');

    // ── Opérations : atelier, chantiers, présences, projets, services ──────
    Route::middleware('module:manufacturing')->group(function () {
        Route::get('/atelier', Livewire\Operations\Manufacturing::class)->name('manufacturing');
        Route::get('/atelier/nomenclatures', Livewire\Operations\Boms::class)->name('manufacturing.boms');
        Route::get('/atelier/{mo}', Livewire\Operations\ManufacturingShow::class)->name('manufacturing.show');
    });
    Route::middleware('module:sites')->group(function () {
        Route::get('/chantiers', Livewire\Operations\Sites::class)->name('sites');
        Route::get('/chantiers/{site}', Livewire\Operations\SiteShow::class)->name('sites.show');
    });
    Route::get('/presences', Livewire\Operations\Attendance::class)->middleware('module:attendance')->name('attendance');
    Route::middleware('module:projects')->group(function () {
        Route::get('/projets', Livewire\Operations\Projects::class)->name('projects');
        Route::get('/projets/{project}', Livewire\Operations\ProjectShow::class)->name('projects.show');
    });
    Route::get('/planification', Livewire\Operations\Planning::class)->middleware('module:planning')->name('planning');
    Route::get('/rendez-vous', Livewire\Operations\Appointments::class)->middleware('module:appointments')->name('appointments');
    Route::get('/feuilles-de-temps', Livewire\Operations\Timesheets::class)->middleware('module:timesheets')->name('timesheets');

    // ── Stock & achats ──────────────────────────────────────────────────────
    Route::get('/stock', Livewire\Stock\Inventory::class)->middleware('module:inventory')->name('inventory');
    Route::middleware('module:purchases')->group(function () {
        Route::get('/achats', Livewire\Stock\Purchases::class)->name('purchases');
        Route::get('/achats/nouveau', Livewire\Sales\DocumentEditor::class)->defaults('type', 'purchase_order')->name('purchases.create');
    });
    Route::get('/bons', Livewire\Stock\Vouchers::class)->middleware('module:vouchers')->name('vouchers');

    // ── Finance ─────────────────────────────────────────────────────────────
    Route::get('/caisse', Livewire\Finance\Cash::class)->middleware('module:cash')->name('cash');
    Route::get('/notes-de-frais', Livewire\Finance\ExpenseClaims::class)->middleware('module:expense-claims')->name('expense-claims');
    Route::get('/comptabilite', Livewire\Finance\Accounting::class)->middleware('module:accounting')->name('accounting');
    Route::get('/tresorerie', Livewire\Finance\Banking::class)->middleware('module:banking')->name('banking');
    Route::get('/rapports', Livewire\Finance\Reports::class)->middleware('module:reports')->name('reports');

    // ── Ressources humaines & paie ──────────────────────────────────────────
    Route::middleware('module:employees')->group(function () {
        Route::get('/employes', Livewire\Hr\Employees::class)->name('employees');
        Route::get('/employes/{employee}', Livewire\Hr\EmployeeShow::class)->name('employees.show');
    });
    Route::get('/conges', Livewire\Hr\Leaves::class)->middleware('module:leaves')->name('leaves');
    Route::middleware('module:payroll')->group(function () {
        Route::get('/paie', Livewire\Hr\Payroll::class)->name('payroll');
        Route::get('/paie/{period}', Livewire\Hr\PayrollShow::class)->name('payroll.show');
    });

    // ── Système ─────────────────────────────────────────────────────────────
    Route::get('/messages', Livewire\Chat\Index::class)->middleware('module:messages')->name('chat');
    Route::get('/systeme', Livewire\System\Master::class)->middleware('module:system')->name('system');

    // ── Impressions PDF (en-tête et pied de page SOGEREF) ───────────────────
    Route::controller(PdfController::class)->prefix('pdf')->name('pdf.')->group(function () {
        Route::get('/document/{document}', 'document')->name('document');
        Route::get('/bon/{voucher}', 'voucher')->middleware('module:vouchers')->name('voucher');
        Route::get('/fabrication/{mo}', 'manufacturing')->middleware('module:manufacturing')->name('manufacturing');
        Route::get('/bulletin/{payslip}', 'payslip')->middleware('module:payroll')->name('payslip');
        Route::get('/paie/{period}', 'payrollPeriod')->middleware('module:payroll')->name('payroll');
        Route::get('/presence/{sheet}', 'attendance')->middleware('module:attendance')->name('attendance');
        Route::get('/chantier/{site}', 'siteReport')->middleware('module:sites')->name('site');
        Route::get('/commande/{order}', 'order')->middleware('module:orders')->name('order');
        Route::get('/ticket/{posOrder}', 'ticket')->middleware('module:pos')->name('ticket');
        Route::get('/rapport/{report}', 'report')->middleware('module:reports')->name('report');
        Route::get('/papier-entete', 'letterhead')->name('letterhead');
    });

    // ── Pièces jointes de la messagerie ─────────────────────────────────────
    Route::get('/messages/fichiers/{attachment}/apercu', function (MessageAttachment $attachment) {
        abort_unless($attachment->message->conversation->participants()->whereKey(auth()->id())->exists(), 403);
        abort_unless($attachment->isImage(), 404);

        return response()->file(Storage::disk($attachment->disk)->path($attachment->path), ['Content-Type' => $attachment->mime_type, 'Content-Disposition' => 'inline; filename="'.addslashes($attachment->original_name).'"']);
    })->name('chat.attachments.preview');
    Route::get('/messages/fichiers/{attachment}/telecharger', function (MessageAttachment $attachment) {
        abort_unless($attachment->message->conversation->participants()->whereKey(auth()->id())->exists(), 403);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    })->name('chat.attachments.download');
});
