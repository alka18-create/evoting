<?php

namespace Database\Factories;

use App\Models\Election;
use App\Models\Voter;
use App\Models\VoterEligibility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoterEligibility>
 */
class VoterEligibilityFactory extends Factory
{
    protected $model = VoterEligibility::class;

    public function definition(): array
    {
        $plain = \App\Support\VotingToken::generate();

        return [
            'election_id' => Election::factory(),
            'voter_id' => Voter::factory(),
            'status' => 'ELIGIBLE',
            'token_hash' => \App\Support\VotingToken::hash($plain),
            'expires_at' => now()->addDays(7),
        ];
    }

    public function withoutToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'token_hash' => null,
            'expires_at' => null,
        ]);
    }

    public function voted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'VOTED',
            'voted_at' => now(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'REVOKED',
        ]);
    }
}