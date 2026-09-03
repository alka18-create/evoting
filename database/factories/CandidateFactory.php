<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Election;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Candidate>
 */
class CandidateFactory extends Factory
{
    protected $model = Candidate::class;

    public function definition(): array
    {
        return [
            'election_id' => Election::factory(),
            'candidate_number' => fake()->unique()->numberBetween(1, 99),
            'name' => fake()->name(),
            'vision' => fake()->paragraph(),
            'mission' => fake()->paragraph(),
        ];
    }

    public function forElection(Election $election, int $number): static
    {
        return $this->state(fn (array $attributes) => [
            'election_id' => $election->id,
            'candidate_number' => $number,
        ]);
    }
}