<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'published_at' => null,
            'pinned_at' => null,
            'created_by_user_id' => User::factory()->officer(),
            'updated_by_user_id' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'published_at' => null,
            'pinned_at' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'published_at' => now(),
            'pinned_at' => null,
        ]);
    }

    public function pinned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'published_at' => now(),
            'pinned_at' => now(),
        ]);
    }
}
