<?php

namespace Database\Factories;

use App\Models\HouseholdMember;
use App\Models\MembershipApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HouseholdMember>
 */
class HouseholdMemberFactory extends Factory
{
    protected $model = HouseholdMember::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'membership_application_id' => MembershipApplication::factory(),
            'name' => fake()->name(),
        ];
    }
}
