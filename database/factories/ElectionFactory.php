<?php

namespace Database\Factories;

use App\Domain\Elections\Enums\ElectionStatus;
use App\Models\Election;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Election>
 */
class ElectionFactory extends Factory
{
    protected $model = Election::class;

    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 week', '+2 weeks');
        $endsAt = (clone $startsAt)->modify('+2 days');

        return [
            'name' => 'Pemilihan ' . fake()->words(2, true),
            'description' => fake()->sentence(),
            'status' => ElectionStatus::Draft,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'created_by' => User::factory(),
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ElectionStatus::Scheduled,
        ]);
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ElectionStatus::Open,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'opened_at' => now()->subHour(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ElectionStatus::Closed,
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->subDay(),
            'opened_at' => now()->subDays(2),
            'closed_at' => now()->subDay(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ElectionStatus::Archived,
        ]);
    }
}