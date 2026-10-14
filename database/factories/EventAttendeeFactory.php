<?php

namespace Database\Factories;

use App\Models\EventAttendee;
use App\Models\EventRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<EventAttendee> */
class EventAttendeeFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['event_registration_id' => EventRegistration::factory(), 'name' => fake()->name(), 'ticket_token' => Str::random(64)];
    }
}
