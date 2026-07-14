<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('sex');
            $table->decimal('amount', 12, 2)->nullable()->after('case_info');
            $table->string('reference_code')->nullable()->unique()->after('amount');
            $table->string('history_key', 64)->nullable()->index()->after('reference_code');
            $table->string('eligibility_status')->default('Eligible')->after('history_key');
            $table->timestamp('eligibility_checked_at')->nullable()->after('eligibility_status');
            $table->foreignId('override_by')->nullable()->after('eligibility_checked_at')->constrained('users')->nullOnDelete();
            $table->text('override_justification')->nullable()->after('override_by');
            $table->timestamp('override_at')->nullable()->after('override_justification');
            $table->timestamp('approved_at')->nullable()->after('override_at');
            $table->timestamp('released_at')->nullable()->after('approved_at');
            $table->timestamp('completed_at')->nullable()->after('released_at');
            $table->timestamp('archived_at')->nullable()->index()->after('completed_at');
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->index(['barangay_id', 'date_of_assistance']);
            $table->index(['assistance_type_id', 'date_of_assistance']);
            $table->index(['status', 'date_of_assistance']);
        });

        DB::table('beneficiaries')->orderBy('id')->get()->each(function ($beneficiary) {
            $birthDate = $beneficiary->birth_date ?: Carbon::now()->subYears((int) $beneficiary->age)->toDateString();
            $referenceCode = $beneficiary->reference_code ?: 'AICS-'.Str::ulid()->toBase32();
            $historyKey = hash('sha1', implode('|', [
                Str::lower(trim((string) $beneficiary->name)),
                $birthDate,
                preg_replace('/\D+/', '', (string) $beneficiary->contact),
                Str::lower(trim((string) $beneficiary->address)),
                (string) $beneficiary->barangay_id,
            ]));

            DB::table('beneficiaries')
                ->where('id', $beneficiary->id)
                ->update([
                    'birth_date' => $birthDate,
                    'reference_code' => $referenceCode,
                    'history_key' => $historyKey,
                    'eligibility_status' => $beneficiary->eligibility_status ?: 'Eligible',
                ]);
        });
    }

    public function down(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropIndex(['barangay_id', 'date_of_assistance']);
            $table->dropIndex(['assistance_type_id', 'date_of_assistance']);
            $table->dropIndex(['status', 'date_of_assistance']);
            $table->dropColumn([
                'birth_date',
                'amount',
                'reference_code',
                'history_key',
                'eligibility_status',
                'eligibility_checked_at',
                'override_by',
                'override_justification',
                'override_at',
                'approved_at',
                'released_at',
                'completed_at',
                'archived_at',
            ]);
        });
    }
};
