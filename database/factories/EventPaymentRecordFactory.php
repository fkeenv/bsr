<?php

namespace Database\Factories;

use App\Models\EventPaymentRecord;
use App\Models\EventRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EventPaymentRecord> */
class EventPaymentRecordFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['event_registration_id' => EventRegistration::factory(), 'amount_centavos' => 10000, 'method' => 'gcash', 'reference' => fake()->uuid(), 'status' => 'submitted'];
    }
}
