<?php

namespace App\Http\Requests\Officer;

use App\Enums\HouseholdPermission;
use App\Support\PlatformAccess;
use Illuminate\Foundation\Http\FormRequest;

class RevokePropertyInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return PlatformAccess::allowsStaff($this->user(), HouseholdPermission::InvitationsRevoke);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
