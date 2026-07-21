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
                'role' => 'admin',
                'status' => 'approved',
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'focal@crisimap.test'],
            [
                'name' => 'AICS Focal Person',
                'password' => Hash::make('password123'),
                'role' => 'focal_person',
                'status' => 'approved',
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'officer@crisimap.test'],
            [
                'name' => 'Social Welfare Officer',
                'password' => Hash::make('password123'),
                'role' => 'social_officer',
                'status' => 'approved',
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(['email' => 'it@crisimap.test'], ['name' => 'System Administrator', 'password' => Hash::make('password123'), 'role' => 'super_admin', 'status' => 'approved', 'email_verified_at' => now()]);
    }
}
