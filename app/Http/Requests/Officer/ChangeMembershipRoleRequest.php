<?php

namespace App\Http\Requests\Officer;

use App\Enums\MembershipRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeMembershipRoleRequest extends FormRequest
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
            'role' => ['required', Rule::enum(MembershipRole::class)],
        ];
    }
}
