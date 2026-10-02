<?php

namespace Database\Factories;

use App\Enums\OnboardingExperience;
use App\Models\OnboardingProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OnboardingProgress>
 */
class OnboardingProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'experience' => OnboardingExperience::Member,
            'version' => OnboardingExperience::Member->currentVersion(),
            'tour_acknowledged_at' => null,
            'completed_steps' => [],
        ];
    }

    public function tourAcknowledged(): static
    {
        return $this->state(fn (array $attributes) => [
            'tour_acknowledged_at' => now(),
        ]);
    }
}
