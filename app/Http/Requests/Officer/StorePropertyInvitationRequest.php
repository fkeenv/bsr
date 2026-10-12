<?php

namespace App\Http\Requests\Officer;

use App\Enums\HouseholdPermission;
use App\Enums\MembershipRole;
use App\Support\PlatformAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return PlatformAccess::allowsStaff($this->user(), HouseholdPermission::InvitationsCreate);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'property_id' => [
                'required',
                'integer',
                Rule::exists('properties', 'id')->where('is_active', true),
            ],
            'role' => ['required', Rule::enum(MembershipRole::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'property_id.exists' => 'Select an active Property.',
        ];
    }
}
