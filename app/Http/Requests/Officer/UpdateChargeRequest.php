<?php

namespace App\Http\Requests\Officer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChargeRequest extends FormRequest
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
            'lines' => ['required', 'array'],
            'lines.*.id' => ['nullable', 'integer', 'exists:charge_lines,id'],
            'lines.*.fee_type_id' => ['nullable', 'integer', 'exists:fee_types,id'],
            'lines.*.fee_type_name' => ['required', 'string', 'max:255'],
            'lines.*.amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
