<?php

namespace Database\Factories;

use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\Election;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ballot>
 */
class BallotFactory extends Factory
{
    protected $model = Ballot::class;

    public function definition(): array
    {
        $candidate = Candidate::factory()->create();

        return [
            'election_id' => $candidate->election_id,
            'candidate_id' => $candidate->id,
            'ballot_hash' => hash('sha256', Str::random(64)),
            'created_at' => now(),
        ];
    }

    public function forElection(Election $election, Candidate $candidate): static
    {
        return $this->state(fn (array $attributes) => [
            'election_id' => $election->id,
            'candidate_id' => $candidate->id,
        ]);
    }
}