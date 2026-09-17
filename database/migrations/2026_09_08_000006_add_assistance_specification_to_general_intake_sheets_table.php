<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_intake_sheets', function (Blueprint $table) {
            $table->string('assistance_specification')->nullable()->after('purpose_of_assistance');
        });
    }

    public function down(): void
    {
        Schema::table('general_intake_sheets', function (Blueprint $table) {
            $table->dropColumn('assistance_specification');
        });
    }
};
