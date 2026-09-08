<?php

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$intake = new App\Models\GeneralIntakeSheet([
    'client_name' => 'Juan Dela Cruz',
    'intake_date' => '2026-09-07',
    'program' => 'AICS',
    'client_type' => 'new',
    'age' => 45,
    'sex' => 'Male',
    'civil_status' => 'Married',
    'occupation' => 'Farmer',
    'monthly_income' => 5000,
    'purpose_of_assistance' => 'Medical Assistance',
]);
$case = new App\Models\CaseRecord(['case_number' => 'CASE-001', 'client_name' => 'Juan Dela Cruz']);
$case->setRelation('householdMembers', collect());
$html = view('intakes.print', [
    'intake' => $intake,
    'caseRecord' => $case,
    'beneficiary' => null,
    'settings' => [],
    'fields' => [],
    'document' => new App\Models\DocumentRecord,
])->render();

Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4')->save(dirname(__DIR__).'/tmp/pdfs/current-intake.pdf');
