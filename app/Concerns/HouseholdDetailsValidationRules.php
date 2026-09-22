<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait HouseholdDetailsValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function householdDetailsRules(): array
    {
        return [
            'household_members' => ['nullable', 'array'],
            'household_members.*.name' => ['required', 'string', 'max:255'],
            'emergency_contacts' => ['nullable', 'array'],
            'emergency_contacts.*.name' => ['required', 'string', 'max:255'],
            'emergency_contacts.*.contact_number' => ['required', 'string', 'max:50'],
            'emergency_contacts.*.relationship' => ['required', 'string', 'max:255'],
            'vehicles' => ['nullable', 'array'],
            'vehicles.*.year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'vehicles.*.make' => ['required', 'string', 'max:255'],
            'vehicles.*.model' => ['required', 'string', 'max:255'],
            'vehicles.*.plate' => ['required', 'string', 'max:50'],
            'vehicles.*.sticker_number' => ['required', 'string', 'max:50'],
        ];
    }
}
