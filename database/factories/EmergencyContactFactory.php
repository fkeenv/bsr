<?php

namespace Database\Factories;

use App\Models\EmergencyContact;
use App\Models\MembershipApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmergencyContact>
 */
class EmergencyContactFactory extends Factory
{
    protected $model = EmergencyContact::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'membership_application_id' => MembershipApplication::factory(),
            'name' => fake()->name(),
            'contact_number' => fake()->numerify('+639#########'),
            'relationship' => fake()->randomElement(['spouse', 'parent', 'sibling', 'friend']),
        ];
    }
}
