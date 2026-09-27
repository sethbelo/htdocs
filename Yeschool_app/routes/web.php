<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return redirect()->route('filament.admin.auth.login');
});

Route::any('/admin/logout', function (\Illuminate\Http\Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('filament.admin.auth.login');
})->name('admin.logout');

Route::get('/admin/select-year/{id}', function ($id) {
    session(['selected_academic_year_id' => $id]);
    return redirect()->back();
})->name('admin.select-year');

Route::get('/admin/export-students-excel', [App\Http\Controllers\StudentExportController::class, 'exportExcel'])->name('admin.export-students-excel');
Route::get('/admin/export-students-pdf', [App\Http\Controllers\StudentExportController::class, 'exportPdf'])->name('admin.export-students-pdf');

Route::get('/admin/export-classes-excel', [App\Http\Controllers\ClassExportController::class, 'exportExcel'])->name('admin.export-classes-excel');
Route::get('/admin/export-classes-pdf', [App\Http\Controllers\ClassExportController::class, 'exportPdf'])->name('admin.export-classes-pdf');

Route::get('/admin/export-years-excel', [App\Http\Controllers\AcademicYearExportController::class, 'exportExcel'])->name('admin.export-years-excel');
Route::get('/admin/export-years-pdf', [App\Http\Controllers\AcademicYearExportController::class, 'exportPdf'])->name('admin.export-years-pdf');
