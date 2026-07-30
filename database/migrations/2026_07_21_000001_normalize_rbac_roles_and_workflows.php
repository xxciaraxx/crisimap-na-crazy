<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'social_welfare_officer')->update(['role' => 'social_officer']);
        DB::table('users')->where('role', 'aics_focal_person')->update(['role' => 'focal_person']);
        DB::table('users')->where('role', 'mswdo_head')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'technical_expert')->update(['role' => 'super_admin']);
        DB::table('users')->where('status', 'active')->update(['status' => 'approved']);
        DB::table('users')->where('status', 'inactive')->update(['status' => 'rejected']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['social_officer', 'focal_person', 'admin', 'super_admin'])->default('social_officer')->change();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->change();
        });

        Schema::table('case_records', function (Blueprint $table) {
            $table->enum('status', ['submitted', 'under_review', 'approved', 'rejected'])->default('submitted')->after('encoded_by');
            $table->text('rejection_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('case_records', function (Blueprint $table) {
            $table->dropColumn(['status', 'rejection_reason']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('social_welfare_officer')->change();
            $table->string('status')->default('active')->change();
        });
    }
};
