<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => env('SUPER_ADMIN_EMAIL', 'superadmin@evoting.local')],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'ChangeMe123!')),
                'role' => UserRole::SuperAdmin,
                'is_active' => true,
            ]
        );
    }
}