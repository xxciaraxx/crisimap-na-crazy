<?php

use App\Models\Barangay;
use App\Support\TagudinBarangays;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (TagudinBarangays::all() as $barangay) {
            // Preserve the IDs of any correctly named existing barangays so
            // beneficiary relationships remain intact.
            $existing = Barangay::query()->where('name', $barangay['name'])->first();

            if ($existing) {
                $existing->update([
                    'code' => $barangay['code'],
                    'notes' => 'Tagudin, Ilocos Sur barangay boundary (PSGC)',
                ]);
                continue;
            }

            Barangay::updateOrCreate(
                ['code' => $barangay['code']],
                ['name' => $barangay['name'], 'notes' => 'Tagudin, Ilocos Sur barangay boundary (PSGC)']
            );
        }
    }

    public function down(): void
    {
        // This is a data correction. Reverting it must not remove barangays
        // that may already be referenced by beneficiary records.
    }
};
