<?php

namespace App\Http\Requests\Officer;

use App\Enums\MembershipRole;
use App\Models\Membership;
use App\Support\MembershipAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeMembershipRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $membership = $this->route('membership');

        return $membership instanceof Membership && MembershipAccess::canChangeRole($this->user(), $membership);
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
