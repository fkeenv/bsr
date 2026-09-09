<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMembershipApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'note' => ['nullable', 'string', 'max:5000'],
            'accept_terms' => ['accepted'],
            'accept_privacy' => ['accepted'],
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
