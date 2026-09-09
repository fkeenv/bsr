<?php

namespace Database\Factories;

use App\Models\FeeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeType>
 */
class FeeTypeFactory extends Factory
{
    protected $model = FeeType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'amount' => '200.00',
            'retired_at' => null,
        ];
    }

    public function retired(): static
    {
        return $this->state(fn (array $attributes) => [
            'retired_at' => now(),
        ]);
    }
}
