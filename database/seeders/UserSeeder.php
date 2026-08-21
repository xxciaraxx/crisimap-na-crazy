<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Default initial System Administrator credentials (change through .env before seeding):
        // Email: systemadmin@crisimap.test | Username: system.admin | Password: ChangeMe!2026
        // The account is required to change these credentials after its first successful sign-in.
        [$email, $name, $username, $role] = [
            env('CRISIMAP_SYSTEM_ADMIN_EMAIL', 'systemadmin@crisimap.test'),
            'System Administrator',
            env('CRISIMAP_SYSTEM_ADMIN_USERNAME', 'system.admin'),
            'super_admin',
        ];
        $password = env('CRISIMAP_SYSTEM_ADMIN_PASSWORD', 'ChangeMe!2026');
        $user = User::firstOrCreate(['email' => $email], [
            'name' => $name,
            'username' => $username,
            'password' => Hash::make($password),
            'role' => $role,
            'status' => 'approved',
            'must_complete_setup' => true,
        ]);

        $this->command?->warn("Initial {$role} credentials: {$email} / {$password}");
    }
}
