<?php

namespace Database\Factories;

use App\Models\FeeType;
use App\Models\Property;
use App\Models\Suspend;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Suspend>
 */
class SuspendFactory extends Factory
{
    protected $model = Suspend::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'fee_type_id' => FeeType::factory(),
            'starts_year' => 2026,
            'starts_month' => 1,
            'ends_year' => null,
            'ends_month' => null,
        ];
    }
}
