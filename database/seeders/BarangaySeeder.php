<?php

namespace Database\Seeders;

use App\Models\Barangay;
use Illuminate\Database\Seeder;

class BarangaySeeder extends Seeder
{
    public function run(): void
    {
        $barangays = [
            'Aglipay', 'Ambalayat', 'Bacud', 'Bario-Patac', 'Bimmotobot', 'Bitalag', 'Cabisilan', 'Cayus', 'Darnay', 'Dinitian', 'Libtong', 'Magsingal', 'Mambug', 'Marnay', 'Nangobongan', 'Padang', 'Pangpang', 'Poblacion', 'Sangbay', 'Tampugo', 'Taro', 'Tay-ac', 'Tinaan', 'Ubbog',
        ];

        foreach ($barangays as $barangay) {
            Barangay::firstOrCreate(['name' => $barangay]);
        }
    }
}
