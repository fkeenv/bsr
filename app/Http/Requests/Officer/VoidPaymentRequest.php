<?php

namespace App\Http\Requests\Officer;

use Illuminate\Foundation\Http\FormRequest;

class VoidPaymentRequest extends FormRequest
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
            'void_reason' => ['required', 'string', 'max:5000'],
        ];
    }
}
