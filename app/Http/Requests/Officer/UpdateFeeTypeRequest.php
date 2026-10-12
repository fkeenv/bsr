<?php

namespace App\Http\Requests\Officer;

use App\Enums\FinancialPermission;
use App\Models\FeeType;
use App\Support\PlatformAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeeTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return PlatformAccess::allowsStaff($this->user(), FinancialPermission::FeeTypesEdit);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var FeeType $feeType */
        $feeType = $this->route('fee_type');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('fee_types', 'name')->ignore($feeType),
            ],
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
