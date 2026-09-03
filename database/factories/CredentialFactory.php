<?php

namespace Database\Factories;

use App\Models\Credential;
use App\Models\VoterEligibility;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Credential>
 */
class CredentialFactory extends Factory
{
    protected $model = Credential::class;

    public function definition(): array
    {
        return [
            'voter_eligibility_id' => VoterEligibility::factory(),
            'credential_hash' => hash('sha256', Str::random(64)),
        ];
    }

    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => now(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now(),
        ]);
    }
}