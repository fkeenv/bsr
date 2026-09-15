<?php

namespace App\Http\Requests\Officer;

use App\Enums\PeriodStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnpaidRosterRequest extends FormRequest
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
            'block' => ['sometimes', 'nullable', 'string', 'max:50'],
            'lot' => ['sometimes', 'nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'nullable', Rule::enum(PeriodStatus::class)],
            'owes_for' => ['sometimes', 'nullable', 'string', 'max:20'],
            'property' => ['sometimes', 'nullable', 'integer', 'exists:properties,id'],
            'charge' => ['sometimes', 'nullable', 'integer', 'exists:charges,id'],
        ];
    }
}
