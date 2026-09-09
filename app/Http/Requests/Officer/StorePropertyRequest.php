<?php

namespace App\Http\Requests\Officer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends FormRequest
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
        return [
            'block' => ['required', 'string', 'max:50'],
            'lot' => [
                'required',
                'string',
                'max:50',
                Rule::unique('properties', 'lot')->where(
                    fn ($query) => $query->where('block', $this->input('block')),
                ),
            ],
            'street_address' => ['nullable', 'string', 'max:255'],
            'recorded_owner_name' => ['nullable', 'string', 'max:255'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lot.unique' => 'A Property with this Block and Lot already exists.',
        ];
    }
}
