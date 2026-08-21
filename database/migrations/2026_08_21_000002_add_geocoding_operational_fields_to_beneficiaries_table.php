<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->timestamp('geocode_attempted_at')->nullable()->after('geocoded_at');
            $table->timestamp('geocode_failed_at')->nullable()->after('geocode_attempted_at');
            $table->string('geocode_failure_reason', 1000)->nullable()->after('geocode_failed_at');
            $table->index('status');
            $table->index('date_of_assistance');
            $table->index('geocode_precision');
        });
    }

    public function down(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['date_of_assistance']);
            $table->dropIndex(['geocode_precision']);
            $table->dropColumn(['geocode_attempted_at', 'geocode_failed_at', 'geocode_failure_reason']);
        });
    }
};
