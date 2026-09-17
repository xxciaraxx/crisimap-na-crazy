<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $colors = [
            'medical' => '#E53935',
            'food' => '#43A047',
            'livelihood' => '#8E24AA',
            'education' => '#1E88E5',
            'transportation' => '#FB8C00',
            'construction materials' => '#FDB813',
            'burial' => '#424242',
        ];

        foreach ($colors as $name => $color) {
            DB::table('assistance_types')
                ->whereRaw('LOWER(TRIM(name)) = ?', [$name])
                ->update(['color' => $color]);
        }
    }

    public function down(): void
    {
        // The previous colors are not recoverable from the database.
    }
};
