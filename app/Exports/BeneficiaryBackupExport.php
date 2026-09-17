<?php

namespace App\Exports;

use App\Models\Beneficiary;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BeneficiaryBackupExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function query()
    {
        return Beneficiary::query()
            ->with(['barangay:id,name', 'assistanceType:id,name'])
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'id', 'reference_code', 'name', 'age', 'sex', 'birth_date', 'contact', 'address',
            'barangay', 'assistance_type', 'case_info', 'amount', 'date_of_assistance', 'status',
            'eligibility_status', 'rejection_reason', 'rejected_at', 'approved_at', 'released_at',
            'completed_at', 'archived_at', 'created_at', 'updated_at',
        ];
    }

    public function map($beneficiary): array
    {
        return [
            $beneficiary->id,
            $beneficiary->reference_code,
            $beneficiary->name,
            $beneficiary->age,
            $beneficiary->sex,
            $this->date($beneficiary->birth_date),
            $beneficiary->contact,
            $beneficiary->address,
            $beneficiary->barangay?->name,
            $beneficiary->assistanceType?->name,
            $beneficiary->case_info,
            $beneficiary->amount,
            $this->date($beneficiary->date_of_assistance),
            $beneficiary->status,
            $beneficiary->eligibility_status,
            $beneficiary->rejection_reason,
            $this->date($beneficiary->rejected_at),
            $this->date($beneficiary->approved_at),
            $this->date($beneficiary->released_at),
            $this->date($beneficiary->completed_at),
            $this->date($beneficiary->archived_at),
            $this->date($beneficiary->created_at),
            $this->date($beneficiary->updated_at),
        ];
    }

    private function date($value): ?string
    {
        return $value ? Carbon::parse($value)->toDateTimeString() : null;
    }
}
