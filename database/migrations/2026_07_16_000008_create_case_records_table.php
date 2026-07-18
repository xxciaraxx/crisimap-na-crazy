<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_records', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique();
            $table->date('date_of_entry');
            $table->string('client_name');
            $table->foreignId('barangay_id')->nullable()->constrained()->nullOnDelete();
            $table->text('problem_presented')->nullable();
            $table->string('house_structure')->nullable();
            $table->string('water_source')->nullable();
            $table->string('electricity')->nullable();
            $table->string('client_signature_path')->nullable();
            $table->foreignId('encoded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_records');
    }
};
