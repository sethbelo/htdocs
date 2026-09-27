<?php

use App\Http\Controllers\Api\SyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ── Sync API (Offline ↔ Cloud) ──────────────────────────
Route::middleware('auth:sanctum')->prefix('sync')->group(function () {
    Route::get('/pull', [SyncController::class, 'pull'])->name('api.sync.pull');
    Route::post('/push', [SyncController::class, 'push'])->name('api.sync.push');
    Route::get('/status', [SyncController::class, 'status'])->name('api.sync.status');
});
