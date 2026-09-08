<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('general_intake_sheets')
            ->orderBy('id')
            ->get(['id', 'intake_date'])
            ->each(function (object $intake, int $index): void {
                $year = date('Y', strtotime((string) $intake->intake_date));

                DB::table('general_intake_sheets')
                    ->where('id', $intake->id)
                    ->update([
                        'intake_number' => 'TAG-AICS-'.$year.'-'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT),
                    ]);
            });
    }

    public function down(): void
    {
        // Intake numbers are permanent public references and are not reverted.
    }
};
