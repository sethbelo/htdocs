<?php

/*
| Réception (courrier, visites, rendez-vous) et Relations clients (annuaire…).
| Inclus dans le groupe auth + active de web.php.
*/

use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\ReceptionController;
use App\Livewire\Crm\Directory;
use App\Livewire\Reception\Mail;
use App\Livewire\Reception\Visits;
use Illuminate\Support\Facades\Route;

// ── Réception : courrier et visites (l'Accueil du jour « reception » est dans web.php) ──
Route::middleware('module:reception')->prefix('reception')->name('reception.')->group(function () {
    Route::get('/courrier', Mail::class)->name('mail');
    Route::get('/courrier/registre', [ReceptionController::class, 'mailRegister'])->name('mail.print');
    Route::get('/courrier/{mailRecord}/scan', [ReceptionController::class, 'mailFile'])->name('mail.file');
    Route::get('/visites', Visits::class)->name('visits');
    Route::get('/visites/{visitor}/badge', [ReceptionController::class, 'visitorBadge'])->name('visits.badge');
});

// ── Relations clients : annuaire ─────────────────────────────────────────────
Route::middleware('module:directory')->prefix('annuaire')->name('directory')->group(function () {
    Route::get('/', Directory::class);
    Route::get('/export', [DirectoryController::class, 'export'])->name('.export');
    Route::get('/impression', [DirectoryController::class, 'print'])->name('.print');
    Route::get('/{contact}/vcard', [DirectoryController::class, 'vcard'])->name('.vcard');
});
