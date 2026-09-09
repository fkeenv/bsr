<?php

namespace Database\Factories;

use App\Models\Charge;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Charge>
 */
class ChargeFactory extends Factory
{
    protected $model = Charge::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'year' => 2026,
            'month' => 9,
            'frozen_at' => null,
        ];
    }

    public function frozen(): static
    {
        return $this->state(fn (array $attributes) => [
            'frozen_at' => now(),
        ]);
    }
}
