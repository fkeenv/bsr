<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyProfile>
 */
class PropertyProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'household_members' => [],
            'emergency_contacts' => [],
            'vehicles' => [],
            'saved_at' => now(),
        ];
    }
}
