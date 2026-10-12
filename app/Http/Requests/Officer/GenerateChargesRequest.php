<?php

namespace App\Http\Requests\Officer;

use App\Enums\FinancialPermission;
use App\Support\PlatformAccess;
use Illuminate\Foundation\Http\FormRequest;

class GenerateChargesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return PlatformAccess::allowsStaff($this->user(), FinancialPermission::ChargesGenerate);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }
}
