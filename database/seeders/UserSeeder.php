<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['admin@crisimap.test', 'MSWDO Administrator', 'mswdo.head', 'admin'],
            ['focal@crisimap.test', 'AICS Focal Person', 'focal.person', 'focal_person'],
            ['officer@crisimap.test', 'Social Welfare Officer', 'social.officer1', 'social_officer'],
            ['it@crisimap.test', 'System Administrator', 'system.admin', 'super_admin'],
        ] as [$email, $name, $username, $role]) {
            $password = Str::password(16);
            $user = User::firstOrCreate(['email' => $email], ['name' => $name, 'username' => $username, 'password' => Hash::make($password), 'role' => $role, 'status' => 'approved', 'must_complete_setup' => true]);
            if ($user->wasRecentlyCreated) {
                $this->command?->warn(\"Temporary {$role} credentials: {$email} / {$password}\");
            }
        }
    }
}
