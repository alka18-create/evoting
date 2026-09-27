<?php

namespace Database\Factories;

use App\Models\Voter;
use App\Models\VotingEvent;
use App\Models\VotingEventVoter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VotingEventVoter>
 */
class VotingEventVoterFactory extends Factory
{
    protected $model = VotingEventVoter::class;

    public function definition(): array
    {
        $plain = \App\Support\VotingToken::generate();

        return [
            'voting_event_id' => VotingEvent::factory(),
            'voter_id' => Voter::factory(),
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
}
