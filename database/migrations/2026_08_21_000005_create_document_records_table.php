<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('general_intake_sheet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('case_record_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('beneficiary_id')->nullable()->constrained()->nullOnDelete();
            $table->string('template');
            $table->string('template_version')->default('1.0');
            $table->string('status')->default('draft');
            $table->json('payload')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->string('snapshot_path')->nullable();
            $table->timestamps();
            $table->unique(['case_record_id', 'beneficiary_id', 'template']);
            $table->index(['status', 'template']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_records');
    }
};
