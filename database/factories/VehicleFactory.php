<?php

namespace Database\Factories;

use App\Models\MembershipApplication;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'membership_application_id' => MembershipApplication::factory(),
            'year' => fake()->numberBetween(2000, 2026),
            'make' => fake()->randomElement(['Toyota', 'Honda', 'Ford', 'Mitsubishi']),
            'model' => fake()->word(),
            'plate' => strtoupper(fake()->bothify('???###')),
            'sticker_number' => fake()->numerify('STK-####'),
        ];
    }
}
