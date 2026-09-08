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
use App\Http\Controllers\DocumentRecordController;
use App\Http\Controllers\DataBackupController;
use App\Http\Controllers\GeneralIntakeSheetController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProtectedFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'setup.complete'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    Route::get('/beneficiaries/duplicate-check', [BeneficiaryController::class, 'duplicateCheck'])->name('beneficiaries.duplicate-check');
    Route::get('/intakes/applicant-history', [GeneralIntakeSheetController::class, 'applicantHistory'])->name('intakes.applicant-history');
    Route::get('/beneficiaries/import', [BeneficiaryImportController::class, 'create'])->name('beneficiaries.import.create');
    Route::post('/beneficiaries/import/preview', [BeneficiaryImportController::class, 'preview'])->name('beneficiaries.import.preview');
    Route::post('/beneficiaries/import', [BeneficiaryImportController::class, 'store'])->name('beneficiaries.import.store');
    Route::get('/applications', [BeneficiaryController::class, 'index'])->name('applications.index');
    Route::get('/beneficiary-records', [BeneficiaryController::class, 'index'])->name('beneficiary-records.index');
    Route::get('/verification/{referenceCode}', [BeneficiaryController::class, 'verify'])->name('beneficiaries.verify');
    Route::resource('case-records', CaseRecordController::class);
    Route::get('/intakes/{intake}/print', [GeneralIntakeSheetController::class, 'print'])->name('intakes.print');
    Route::get('/applications/intakes/{intake}/review', [GeneralIntakeSheetController::class, 'review'])->name('applications.intakes.review');
    Route::resource('intakes', GeneralIntakeSheetController::class)->except('destroy');
    Route::post('/intakes/{intake}/submit', [GeneralIntakeSheetController::class, 'submit'])->name('intakes.submit');
    Route::post('/intakes/{intake}/decide', [GeneralIntakeSheetController::class, 'decide'])->name('intakes.decide');
    Route::post('/intakes/{intake}/head-decide', [GeneralIntakeSheetController::class, 'headDecide'])->name('intakes.head-decide');
    Route::get('/case-records/{caseRecord}/documents', [DocumentRecordController::class, 'index'])->name('case-records.documents.index');
    Route::get('/case-records/{caseRecord}/documents/{document}/edit', [DocumentRecordController::class, 'edit'])->name('case-records.documents.edit');
    Route::put('/case-records/{caseRecord}/documents/{document}', [DocumentRecordController::class, 'update'])->name('case-records.documents.update');
    Route::get('/case-records/{caseRecord}/documents/{document}/preview', [DocumentRecordController::class, 'preview'])->name('case-records.documents.preview');
    Route::get('/case-records/{caseRecord}/documents/{document}/snapshot', [ProtectedFileController::class, 'documentSnapshot'])->name('case-records.documents.snapshot');
    Route::get('/case-records/{caseRecord}/signature', [ProtectedFileController::class, 'caseSignature'])->name('case-records.signature');
    Route::post('/case-records/{caseRecord}/documents/{document}/finalize', [DocumentRecordController::class, 'finalize'])->name('case-records.documents.finalize');
    Route::get('/case-records/{caseRecord}/print', [CaseRecordController::class, 'print'])->name('case-records.print');
    Route::post('/case-records/{caseRecord}/decide', [CaseRecordController::class, 'decideEligibility'])->name('case-records.decide');
    Route::resource('beneficiaries', BeneficiaryController::class)->except(['create', 'store']);
    Route::get('/attachments/{attachment}/download', [ProtectedFileController::class, 'attachment'])->name('attachments.download');
    Route::get('/beneficiaries/{beneficiary}/decide', [BeneficiaryController::class, 'decisionRedirect'])->name('beneficiaries.decide.redirect');
    Route::post('/beneficiaries/{beneficiary}/decide', [BeneficiaryController::class, 'decide'])->name('beneficiaries.decide');
    Route::post('/beneficiaries/{beneficiary}/release', [BeneficiaryController::class, 'release'])->name('beneficiaries.release');
    Route::get('/beneficiaries/{beneficiary}/application-form', [BeneficiaryController::class, 'applicationForm'])->name('beneficiaries.application-form');
    Route::get('/beneficiaries/{beneficiary}/application-form/edit', [BeneficiaryController::class, 'editApplicationForm'])->name('beneficiaries.application-form.edit');
    Route::put('/beneficiaries/{beneficiary}/application-form', [BeneficiaryController::class, 'updateApplicationForm'])->name('beneficiaries.application-form.update');
    Route::get('/beneficiaries/{beneficiary}/certificate', [BeneficiaryController::class, 'certificate'])->name('beneficiaries.certificate');
    Route::get('/map', [MapController::class, 'index'])->name('map');
    Route::get('/map/data', [MapController::class, 'data'])->name('map.data');
    Route::get('/map/boundaries', [MapController::class, 'boundaries'])->name('map.boundaries');
    require __DIR__.'/geocode.php';

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
    Route::get('/reports/excel', [ReportController::class, 'excel'])->name('reports.excel');
    Route::middleware('role:focal_person,admin,super_admin')->group(function () {
        Route::get('/data-backup', [DataBackupController::class, 'index'])->name('data-backup.index');
        Route::get('/data-backup/export', [DataBackupController::class, 'export'])->name('data-backup.export');
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings/barangays', [SettingsController::class, 'storeBarangay'])->name('settings.barangays.store');
        Route::delete('/settings/barangays/{barangay}', [SettingsController::class, 'destroyBarangay'])->name('settings.barangays.destroy');
        Route::post('/settings/assistance-types', [SettingsController::class, 'storeAssistanceType'])->name('settings.assistance-types.store');
        Route::put('/settings/official-documents', [SettingsController::class, 'updateOfficialSettings'])->name('settings.official-documents.update');
        Route::delete('/settings/assistance-types/{assistanceType}', [SettingsController::class, 'destroyAssistanceType'])->name('settings.assistance-types.destroy');
        Route::resource('users', UserController::class);
        Route::post('/users/{user}/approve', [UserController::class, 'approve'])->name('users.approve');
        Route::post('/users/{user}/reject', [UserController::class, 'reject'])->name('users.reject');
    });
    Route::middleware('role:admin,super_admin')->group(function () {
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });
});

Route::middleware(['auth', 'setup.complete'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
