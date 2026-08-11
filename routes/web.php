<?php

use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\CaseRecordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BeneficiaryImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified', 'setup.complete'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/beneficiaries/duplicate-check', [BeneficiaryController::class, 'duplicateCheck'])->name('beneficiaries.duplicate-check');
    Route::get('/beneficiaries/import', [BeneficiaryImportController::class, 'create'])->name('beneficiaries.import.create');
    Route::post('/beneficiaries/import/preview', [BeneficiaryImportController::class, 'preview'])->name('beneficiaries.import.preview');
    Route::post('/beneficiaries/import', [BeneficiaryImportController::class, 'store'])->name('beneficiaries.import.store');
    Route::get('/verification/{referenceCode}', [BeneficiaryController::class, 'verify'])->name('beneficiaries.verify');
    Route::resource('case-records', CaseRecordController::class);
    Route::get('/case-records/{caseRecord}/print', [CaseRecordController::class, 'print'])->name('case-records.print');
    Route::post('/case-records/{caseRecord}/advance', [CaseRecordController::class, 'advance'])->name('case-records.advance');
    Route::post('/case-records/{caseRecord}/decision', [CaseRecordController::class, 'decide'])->name('case-records.decision');
    Route::resource('beneficiaries', BeneficiaryController::class);
    Route::post('/beneficiaries/{beneficiary}/reject', [BeneficiaryController::class, 'reject'])->name('beneficiaries.reject');
    Route::get('/beneficiaries/{beneficiary}/application-form', [BeneficiaryController::class, 'applicationForm'])->name('beneficiaries.application-form');
    Route::get('/beneficiaries/{beneficiary}/certificate', [BeneficiaryController::class, 'certificate'])->name('beneficiaries.certificate');
    Route::get('/map', [MapController::class, 'index'])->name('map');
    Route::get('/map/data', [MapController::class, 'data'])->name('map.data');
    Route::get('/map/boundaries', [MapController::class, 'boundaries'])->name('map.boundaries');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
    Route::get('/reports/excel', [ReportController::class, 'excel'])->name('reports.excel');

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings/barangays', [SettingsController::class, 'storeBarangay'])->name('settings.barangays.store');
        Route::delete('/settings/barangays/{barangay}', [SettingsController::class, 'destroyBarangay'])->name('settings.barangays.destroy');
        Route::post('/settings/assistance-types', [SettingsController::class, 'storeAssistanceType'])->name('settings.assistance-types.store');
        Route::delete('/settings/assistance-types/{assistanceType}', [SettingsController::class, 'destroyAssistanceType'])->name('settings.assistance-types.destroy');
        Route::resource('users', UserController::class);
    });
    Route::middleware('role:admin,super_admin')->group(function () {
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });
});

Route::middleware(['auth', 'verified', 'setup.complete'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
