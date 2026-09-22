<?php

namespace App\Http\Requests;

use App\Concerns\HouseholdDetailsValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreMembershipApplicationRequest extends FormRequest
{
    use HouseholdDetailsValidationRules;

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
            ...$this->householdDetailsRules(),
        ];
    }
}
