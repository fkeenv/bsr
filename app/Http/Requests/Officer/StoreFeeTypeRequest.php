<?php

namespace App\Http\Requests\Officer;

use App\Enums\FinancialPermission;
use App\Support\PlatformAccess;
use Illuminate\Foundation\Http\FormRequest;

class StoreFeeTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return PlatformAccess::allowsStaff($this->user(), FinancialPermission::FeeTypesCreate);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:fee_types,name'],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'A Fee Type with this name already exists.',
        ];
    }
}
