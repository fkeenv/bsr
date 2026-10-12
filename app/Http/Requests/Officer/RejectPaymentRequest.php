<?php

namespace App\Http\Requests\Officer;

use App\Enums\FinancialPermission;
use App\Support\PlatformAccess;
use Illuminate\Foundation\Http\FormRequest;

class RejectPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return PlatformAccess::allowsStaff($this->user(), FinancialPermission::PaymentsReject);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:5000'],
        ];
    }
}
