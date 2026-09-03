<?php

namespace Database\Factories;

use App\Models\Voter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voter>
 */
class VoterFactory extends Factory
{
    protected $model = Voter::class;

    public function definition(): array
    {
        return [
            'student_id' => fake()->unique()->numerify('########'),
            'name' => fake()->name(),
            'class_name' => fake()->randomElement(['X-A', 'X-B', 'XI-A', 'XI-B', 'XII-A', 'XII-B']),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}