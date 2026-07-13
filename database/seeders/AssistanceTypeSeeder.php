<?php

namespace Database\Seeders;

use App\Models\AssistanceType;
use Illuminate\Database\Seeder;

class AssistanceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Medical', 'color' => '#D32F2F', 'description' => 'Medical assistance'],
            ['name' => 'Food', 'color' => '#2E7D32', 'description' => 'Food packs'],
            ['name' => 'Livelihood', 'color' => '#1565C0', 'description' => 'Livelihood support'],
            ['name' => 'Education', 'color' => '#FBC02D', 'description' => 'School-related support'],
            ['name' => 'Transportation', 'color' => '#2563eb', 'description' => 'Transport support'],
            ['name' => 'Construction Materials', 'color' => '#7c2d12', 'description' => 'Construction aid'],
            ['name' => 'Burial', 'color' => '#6b7280', 'description' => 'Burial assistance'],
        ];

        foreach ($types as $type) {
            AssistanceType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
