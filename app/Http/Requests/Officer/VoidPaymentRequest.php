<?php

namespace App\Http\Requests\Officer;

use App\Enums\FinancialPermission;
use App\Support\PlatformAccess;
use Illuminate\Foundation\Http\FormRequest;

class VoidPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return PlatformAccess::allowsStaff($this->user(), FinancialPermission::PaymentsVoid);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'void_reason' => ['required', 'string', 'max:5000'],
        ];
    }
}
