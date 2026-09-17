<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table): void {
            $table->foreignId('general_intake_sheet_id')
                ->nullable()
                ->after('case_record_id')
                ->constrained('general_intake_sheets')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('general_intake_sheet_id');
        });
    }
};
