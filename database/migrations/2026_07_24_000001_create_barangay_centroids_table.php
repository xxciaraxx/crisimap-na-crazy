<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barangay_centroids', function (Blueprint $table) {
            $table->id();
            $table->string('psgc_code', 10)->unique();
            $table->string('barangay')->unique();
            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);
            $table->decimal('centroid_latitude', 10, 6);
            $table->decimal('centroid_longitude', 10, 6);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barangay_centroids');
    }
};
