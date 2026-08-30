<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_intake_sheets', function (Blueprint $table) {
            $table->json('family_composition')->nullable()->after('diagnosis_or_cause_of_death');
            $table->decimal('combined_family_income', 12, 2)->nullable()->after('income_resources');
            $table->integer('employed_members_count')->nullable()->after('combined_family_income');
            $table->integer('seasonal_members_count')->nullable()->after('employed_members_count');
        });
    }

    public function down(): void
    {
        Schema::table('general_intake_sheets', function (Blueprint $table) {
            $table->dropColumn(['family_composition', 'combined_family_income', 'employed_members_count', 'seasonal_members_count']);
        });
    }
};
