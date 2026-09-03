<?php

namespace Database\Factories;

use App\Domain\Elections\Enums\VotingEventStatus;
use App\Models\User;
use App\Models\VotingEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VotingEvent>
 */
class VotingEventFactory extends Factory
{
    protected $model = VotingEvent::class;

    public function definition(): array
    {
        $name = 'Event ' . fake()->words(2, true);
        $startsAt = fake()->dateTimeBetween('+1 week', '+2 weeks');
        $endsAt = (clone $startsAt)->modify('+2 days');

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name) . '-' . fake()->unique()->randomNumber(5),
            'description' => fake()->sentence(),
            'status' => VotingEventStatus::Draft,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'created_by' => User::factory(),
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VotingEventStatus::Open,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'opened_at' => now()->subHour(),
        ]);
    }
}
