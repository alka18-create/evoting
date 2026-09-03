<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Aman untuk production: hanya membuat SUPER_ADMIN default.
     * Data demo ada di DevelopmentSeeder (jangan dijalankan di production).
     */
    public function run(): void
    {
        $this->call([
            SuperAdminSeeder::class,
        ]);
    }
}