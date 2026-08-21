<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_records', function (Blueprint $table) {
            $table->boolean('is_government_program_member')->default(false)->after('electricity');
            $table->json('government_programs')->nullable()->after('is_government_program_member');
            $table->string('other_government_program')->nullable()->after('government_programs');
        });
    }

    public function down(): void
    {
        Schema::table('case_records', function (Blueprint $table) {
            $table->dropColumn(['is_government_program_member', 'government_programs', 'other_government_program']);
        });
    }
};
