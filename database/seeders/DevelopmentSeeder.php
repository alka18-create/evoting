<?php

namespace Database\Seeders;

use App\Domain\Elections\Enums\ElectionStatus;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Database\Seeder;

/**
 * Data demo — JANGAN dijalankan di production.
 */
class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('DevelopmentSeeder tidak boleh dijalankan di production.');
        }

        $admin = User::factory()->admin()->create([
            'name' => 'Admin Demo',
            'email' => 'admin@evoting.local',
            'username' => 'admin',
            'is_active' => true,
        ]);

        $operator = User::factory()->operator()->create([
            'name' => 'Operator Demo',
            'email' => 'operator@evoting.local',
            'username' => 'operator',
            'is_active' => false,
        ]);

        // Akun pasif untuk simulasi aktivasi oleh SUPER_ADMIN
        User::factory()->admin()->create([
            'name' => 'Admin Pasif',
            'email' => 'admin.pasif@evoting.local',
            'username' => 'admin.pasif',
            'is_active' => false,
        ]);

        User::factory()->operator()->create([
            'name' => 'Operator Pasif',
            'email' => 'operator.pasif@evoting.local',
            'username' => 'operator.pasif',
            'is_active' => false,
        ]);

        $election = Election::create([
            'name' => 'Pemilihan OSIS Demo 2026',
            'description' => 'Election demo untuk pengujian. Semua data sintetis.',
            'status' => ElectionStatus::Draft,
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(6),
            'created_by' => $admin->id,
        ]);

        $candidateData = [
            ['candidate_number' => 1, 'name' => 'Kandidat A'],
            ['candidate_number' => 2, 'name' => 'Kandidat B'],
            ['candidate_number' => 3, 'name' => 'Kandidat C'],
        ];

        foreach ($candidateData as $data) {
            Candidate::create([...$data, 'election_id' => $election->id]);
        }

        $voters = Voter::factory()->count(25)->create();

        foreach ($voters as $voter) {
            $voter->eligibilities()->create([
                'election_id' => $election->id,
            ]);
        }

        User::factory()->count(3)->create();
    }
}