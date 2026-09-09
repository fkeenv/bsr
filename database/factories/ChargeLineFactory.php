<?php

namespace Database\Factories;

use App\Models\Charge;
use App\Models\ChargeLine;
use App\Models\FeeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChargeLine>
 */
class ChargeLineFactory extends Factory
{
    protected $model = ChargeLine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'charge_id' => Charge::factory(),
            'fee_type_id' => FeeType::factory(),
            'fee_type_name' => fake()->words(2, true),
            'amount' => '200.00',
        ];
    }
}
