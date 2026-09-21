<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\CaseRecord;
use App\Models\GeneralIntakeSheet;

class BeneficiaryFromIntakeService
{
    public function createFrom(CaseRecord $caseRecord, int $assistanceTypeId): Beneficiary
    {
        $caseRecord->loadMissing('intake');
        $intake = $caseRecord->intake;

        $beneficiary = Beneficiary::create([
            'name' => $intake?->beneficiary_name ?: $intake?->client_name ?: $caseRecord->client_name,
            'case_record_id' => $caseRecord->id,
            'general_intake_sheet_id' => $caseRecord->general_intake_sheet_id,
            'age' => $intake?->age ?? 0,
            'sex' => $intake?->sex ?: 'Other',
            'contact' => $intake?->contact,
            'barangay_id' => $intake?->barangay_id ?? $caseRecord->barangay_id,
            'assistance_type_id' => $assistanceTypeId,
            'address' => $intake?->address,
            'amount' => $intake?->requested_amount,
            'case_info' => $intake?->purpose_of_assistance ?: $caseRecord->problem_presented,
            'status' => 'Pending',
            'encoded_by' => $caseRecord->encoded_by,
        ]);

        return app(BeneficiaryEligibilityChecker::class)->apply($beneficiary);
    }

    public function create(GeneralIntakeSheet $intake, int $assistanceTypeId): Beneficiary
    {
        $beneficiary = Beneficiary::create([
            'name' => $intake->beneficiary_name ?: $intake->client_name,
            'general_intake_sheet_id' => $intake->id,
            'age' => $intake->age ?? 0,
            'sex' => $intake->sex ?: 'Other',
            'contact' => $intake->contact,
            'barangay_id' => $intake->barangay_id,
            'assistance_type_id' => $assistanceTypeId,
            'address' => $intake->address,
            'amount' => $intake->requested_amount,
            'case_info' => $intake->purpose_of_assistance,
            'status' => 'Pending',
            'encoded_by' => auth()->id(),
        ]);

        return app(BeneficiaryEligibilityChecker::class)->apply($beneficiary);
    }
}
