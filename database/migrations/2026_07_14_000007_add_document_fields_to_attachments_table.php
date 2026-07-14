<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->string('document_type')->default('supporting_document')->after('name');
            $table->foreignId('uploaded_by')->nullable()->after('document_type')->constrained('users')->nullOnDelete();
            $table->index(['beneficiary_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropIndex(['beneficiary_id', 'document_type']);
            $table->dropColumn(['document_type', 'uploaded_by']);
        });
    }
};
