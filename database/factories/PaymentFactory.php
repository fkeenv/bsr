<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'declared_by_user_id' => User::factory(),
            'amount' => '500.00',
            'method' => PaymentMethod::GCash,
            'reference' => fake()->bothify('REF-####'),
            'screenshot_path' => 'payment-receipts/example.jpg',
            'status' => PaymentStatus::Pending,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Pending,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Confirmed,
            'confirmed_at' => now(),
            'confirmed_by_user_id' => User::factory(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Rejected,
            'rejection_reason' => 'Unmatched transfer',
            'rejected_at' => now(),
            'rejected_by_user_id' => User::factory(),
        ]);
    }
}
