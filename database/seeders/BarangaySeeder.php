<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Support\TagudinBarangays;
use Illuminate\Database\Seeder;

class BarangaySeeder extends Seeder
{
    public function run(): void
    {
        foreach (TagudinBarangays::all() as $barangay) {
            $record = Barangay::query()->where('code', $barangay['code'])->first()
                ?? Barangay::query()->where('name', $barangay['name'])->first()
                ?? new Barangay();

            $record->fill([
                'name' => $barangay['name'],
                'code' => $barangay['code'],
                'notes' => 'Tagudin, Ilocos Sur barangay boundary (PSGC)',
            ])->save();
        }
    }
}
