<?php

namespace Database\Factories;

use App\Models\EventCancellationRequest;
use App\Models\EventRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EventCancellationRequest> */
class EventCancellationRequestFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['event_registration_id' => EventRegistration::factory(), 'submission_key' => fake()->uuid(), 'reason' => fake()->sentence(), 'status' => 'pending'];
    }
}
