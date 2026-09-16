<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $excludedTypeIds = DB::table('assistance_types')
                ->whereRaw("LOWER(TRIM(name)) IN (?, ?, ?)", ['other', 'others', 'other / miscellaneous'])
                ->pluck('id');

            $beneficiaries = DB::table('beneficiaries')
                ->whereNull('assistance_type_id')
                ->orWhereIn('assistance_type_id', $excludedTypeIds)
                ->get(['id', 'general_intake_sheet_id', 'case_record_id']);

            if ($beneficiaries->isNotEmpty()) {
                $beneficiaryIds = $beneficiaries->pluck('id');
                $intakeIds = $beneficiaries->pluck('general_intake_sheet_id')->filter()->unique();
                $caseIds = $beneficiaries->pluck('case_record_id')->filter()->unique();

                DB::table('document_records')
                    ->whereIn('beneficiary_id', $beneficiaryIds)
                    ->delete();
                DB::table('beneficiaries')->whereIn('id', $beneficiaryIds)->delete();

                $remainingIntakeIds = DB::table('beneficiaries')
                    ->whereIn('general_intake_sheet_id', $intakeIds)
                    ->pluck('general_intake_sheet_id');
                $remainingCaseIds = DB::table('beneficiaries')
                    ->whereIn('case_record_id', $caseIds)
                    ->pluck('case_record_id');

                DB::table('document_records')->whereIn('general_intake_sheet_id', $intakeIds)->delete();
                DB::table('document_records')->whereIn('case_record_id', $caseIds)->delete();
                DB::table('case_records')->whereIn('id', $caseIds->diff($remainingCaseIds))->delete();
                DB::table('general_intake_sheets')->whereIn('id', $intakeIds->diff($remainingIntakeIds))->delete();
            }

            DB::table('assistance_types')->whereIn('id', $excludedTypeIds)->delete();
        });
    }

    public function down(): void
    {
        // Deleted records and assistance types cannot be restored automatically.
    }
};
