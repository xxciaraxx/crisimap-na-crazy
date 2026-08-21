<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_records', function (Blueprint $table) {
            $table->foreignId('general_intake_sheet_id')->nullable()->unique()->after('id')->constrained()->nullOnDelete();
        });

        // Preserve legacy history: every existing case gets a minimal, clearly
        // labelled intake record rather than being hidden by the new workflow.
        DB::table('case_records')->whereNull('general_intake_sheet_id')->orderBy('id')->each(function ($case) {
            $now = now();
            $intakeId = DB::table('general_intake_sheets')->insertGetId([
                'intake_number' => 'LEGACY-'.str_pad((string) $case->id, 8, '0', STR_PAD_LEFT),
                'intake_date' => $case->date_of_entry,
                'program' => 'AICS',
                'client_type' => 'returning',
                'client_name' => $case->client_name,
                'barangay_id' => $case->barangay_id,
                'purpose_of_assistance' => $case->problem_presented,
                'social_worker_assessment' => 'Backfilled from existing Case Record '.$case->case_number.'.',
                'encoded_by' => $case->encoded_by,
                'status' => 'legacy',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('case_records')->where('id', $case->id)->update(['general_intake_sheet_id' => $intakeId]);
        });
    }

    public function down(): void
    {
        Schema::table('case_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('general_intake_sheet_id');
        });
    }
};
