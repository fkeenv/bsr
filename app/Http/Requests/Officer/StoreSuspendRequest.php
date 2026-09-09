<?php

namespace App\Http\Requests\Officer;

use Illuminate\Foundation\Http\FormRequest;

class StoreSuspendRequest extends FormRequest
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
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'fee_type_id' => ['required', 'integer', 'exists:fee_types,id'],
            'starts_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'starts_month' => ['required', 'integer', 'min:1', 'max:12'],
            'ends_year' => ['nullable', 'integer', 'min:2000', 'max:2100', 'required_with:ends_month'],
            'ends_month' => ['nullable', 'integer', 'min:1', 'max:12', 'required_with:ends_year'],
        ];
    }
}
