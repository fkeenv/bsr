<?php

namespace App\Http\Requests\Officer;

use App\Models\Property;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessOfficerSurfaces() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Property $property */
        $property = $this->route('property');

        $rules = [
            'street_address' => ['nullable', 'string', 'max:255'],
            'recorded_owner_name' => ['nullable', 'string', 'max:255'],
        ];

        if (! $property->openingBalanceIsFrozen()) {
            $rules['opening_balance'] = ['nullable', 'numeric', 'min:0'];
        }

        return $rules;
    }
}
