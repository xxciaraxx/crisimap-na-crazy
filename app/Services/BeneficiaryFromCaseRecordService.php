<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\CaseRecord;

class BeneficiaryFromCaseRecordService
{
    public function createFrom(CaseRecord $caseRecord, int $assistanceTypeId): Beneficiary
    {
        $member = $caseRecord->householdMembers()->where('name', $caseRecord->client_name)->first() ?? $caseRecord->householdMembers()->first();
        $beneficiary = Beneficiary::create(['case_record_id' => $caseRecord->id, 'name' => $caseRecord->client_name, 'age' => $member?->age ?? 0, 'birth_date' => $member?->birth_date, 'sex' => $member?->sex ?: 'Other', 'barangay_id' => $caseRecord->barangay_id, 'assistance_type_id' => $assistanceTypeId, 'case_info' => $caseRecord->problem_presented, 'status' => 'Pending', 'encoded_by' => auth()->id()]);
        return app(BeneficiaryEligibilityChecker::class)->apply($beneficiary);
    }
}
