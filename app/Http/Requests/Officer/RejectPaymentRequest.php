<?php

namespace App\Http\Requests\Officer;

use Illuminate\Foundation\Http\FormRequest;

class RejectPaymentRequest extends FormRequest
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
            'rejection_reason' => ['required', 'string', 'max:5000'],
        ];
    }
}
