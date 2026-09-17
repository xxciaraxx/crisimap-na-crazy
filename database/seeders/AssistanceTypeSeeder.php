<?php

namespace Database\Seeders;

use App\Models\AssistanceType;
use Illuminate\Database\Seeder;

class AssistanceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Medical', 'color' => '#E53935', 'description' => 'Medical assistance'],
            ['name' => 'Food', 'color' => '#43A047', 'description' => 'Food packs'],
            ['name' => 'Livelihood', 'color' => '#8E24AA', 'description' => 'Livelihood support'],
            ['name' => 'Education', 'color' => '#1E88E5', 'description' => 'School-related support'],
            ['name' => 'Transportation', 'color' => '#FB8C00', 'description' => 'Transport support'],
            ['name' => 'Construction Materials', 'color' => '#FDB813', 'description' => 'Construction aid'],
            ['name' => 'Burial', 'color' => '#424242', 'description' => 'Burial assistance'],
        ];

        foreach ($types as $type) {
            AssistanceType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
