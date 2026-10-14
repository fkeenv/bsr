<?php

namespace Database\Factories;

use App\Models\EventPaymentRecord;
use App\Models\EventRefundRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EventRefundRecord> */
class EventRefundRecordFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['event_payment_record_id' => EventPaymentRecord::factory(), 'submission_key' => fake()->uuid(), 'amount_centavos' => 10000, 'method' => 'cash', 'reference' => fake()->uuid(), 'reason' => fake()->sentence(), 'recorded_by_user_id' => User::factory(), 'refunded_at' => now()];
    }
}
