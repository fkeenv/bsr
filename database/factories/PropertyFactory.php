<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'block' => (string) fake()->unique()->numberBetween(1, 99),
            'lot' => (string) fake()->unique()->numberBetween(1, 99),
            'street_address' => fake()->optional()->streetAddress(),
            'recorded_owner_name' => fake()->optional()->name(),
            'opening_balance' => '0.00',
            'opening_balance_frozen_at' => null,
            'first_charged_at' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withOpeningBalance(string $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'opening_balance' => $amount,
        ]);
    }

    public function frozenOpeningBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'opening_balance_frozen_at' => now(),
        ]);
    }

    public function charged(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_charged_at' => now(),
        ]);
    }
}
