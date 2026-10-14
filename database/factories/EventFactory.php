<?php

namespace Database\Factories;

use App\Enums\EventAudience;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Event> */
class EventFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['title' => fake()->sentence(), 'description' => '<p>Join the community.</p>', 'venue' => 'Association clubhouse', 'starts_at' => now()->addMonth(), 'ends_at' => now()->addMonth()->addHours(2), 'registration_deadline' => now()->addWeeks(3), 'audience' => EventAudience::Public, 'status' => EventStatus::Draft, 'price_centavos' => 0, 'created_by_user_id' => User::factory()];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => EventStatus::Published, 'published_at' => now()]);
    }
}
