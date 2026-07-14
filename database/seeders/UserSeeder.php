<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@crisimap.test'],
            [
                'name' => 'MSWDO Administrator',
                'password' => Hash::make('password123'),
                'role' => 'mswdo_head',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'focal@crisimap.test'],
            [
                'name' => 'AICS Focal Person',
                'password' => Hash::make('password123'),
                'role' => 'aics_focal_person',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'officer@crisimap.test'],
            [
                'name' => 'Social Welfare Officer',
                'password' => Hash::make('password123'),
                'role' => 'social_welfare_officer',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
    }
}
