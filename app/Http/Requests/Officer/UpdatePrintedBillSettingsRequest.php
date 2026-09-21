<?php

namespace App\Http\Requests\Officer;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrintedBillSettingsRequest extends FormRequest
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
            'letterhead_name' => ['required', 'string', 'max:255'],
            'letterhead_short_name' => ['required', 'string', 'max:64'],
            'letterhead_address_lines' => ['required', 'array', 'min:1'],
            'letterhead_address_lines.*' => ['required', 'string', 'max:255'],
            'letterhead_contact' => ['required', 'string', 'max:255'],
            'letterhead_treasurer' => ['required', 'string', 'max:255'],
            'payment_channels' => ['required', 'array', 'min:1'],
            'payment_channels.*.method' => ['required', 'string', 'max:64'],
            'payment_channels.*.detail' => ['required', 'string', 'max:500'],
        ];
    }
}
