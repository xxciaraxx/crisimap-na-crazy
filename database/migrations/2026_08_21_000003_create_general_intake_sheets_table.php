<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_intake_sheets', function (Blueprint $table) {
            $table->id();
            $table->string('intake_number')->unique();
            $table->date('intake_date');
            $table->string('program')->default('AICS');
            $table->string('client_type')->default('new');
            $table->string('client_name');
            $table->string('beneficiary_name')->nullable();
            $table->integer('age')->nullable();
            $table->string('sex', 20)->nullable();
            $table->string('civil_status')->nullable();
            $table->string('occupation')->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->string('contact')->nullable();
            $table->foreignId('barangay_id')->nullable()->constrained()->nullOnDelete();
            $table->text('address')->nullable();
            $table->text('purpose_of_assistance')->nullable();
            $table->decimal('requested_amount', 12, 2)->nullable();
            $table->text('diagnosis_or_cause_of_death')->nullable();
            $table->json('client_sectors')->nullable();
            $table->json('income_resources')->nullable();
            $table->decimal('monthly_expenses', 12, 2)->nullable();
            $table->json('supporting_documents')->nullable();
            $table->text('social_worker_assessment')->nullable();
            $table->string('applicant_signature_path')->nullable();
            $table->string('interviewer_name')->nullable();
            $table->string('interviewer_position')->nullable();
            $table->foreignId('encoded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->index(['status', 'intake_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_intake_sheets');
    }
};
