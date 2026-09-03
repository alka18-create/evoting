<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class FixSuperAdmin extends Command
{
    protected $signature = 'admin:fix-superadmin {--password=ChangeMe123!}';
    protected $description = 'Fix or create superadmin user';

    public function handle(): int
    {
        $password = $this->option('password');
        
        $user = User::where('email', 'superadmin@evoting.local')
            ->orWhere('username', 'superadmin')
            ->first();

        if ($user) {
            $this->info("User ditemukan: {$user->username} ({$user->email})");
            $this->info("Role: " . ($user->role?->value ?? 'NULL'));
            $this->info("is_active: " . ($user->is_active ? 'true' : 'false'));
            
            $user->update([
                'password' => $password,
                'role' => UserRole::SuperAdmin,
                'is_active' => true,
            ]);
            
            $this->info("Password dan role telah diperbarui!");
        } else {
            $user = User::create([
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'email' => 'superadmin@evoting.local',
                'password' => $password,
                'role' => UserRole::SuperAdmin,
                'is_active' => true,
            ]);
            
            $this->info("Superadmin baru dibuat!");
        }

        $this->info("Username: superadmin");
        $this->info("Email: superadmin@evoting.local");
        $this->info("Password: {$password}");
        
        return self::SUCCESS;
    }
}
