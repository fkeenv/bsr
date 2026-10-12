<?php

namespace App\Http\Requests\Officer;

use App\Enums\FinancialPermission;
use App\Enums\PaymentMethod;
use App\Support\PlatformAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConfirmedPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return PlatformAccess::allowsStaff($this->user(), FinancialPermission::PaymentsCreate);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:255'],
            'screenshot' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
