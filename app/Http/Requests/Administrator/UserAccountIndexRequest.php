<?php

namespace App\Http\Requests\Administrator;

use App\Enums\MembershipRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserAccountIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdministratorSurfaces() ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:200'], 'staff_role' => ['nullable', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')->whereNotIn('name', ['member', 'user'])],
            'membership_role' => ['nullable', Rule::enum(MembershipRole::class)], 'live_membership' => ['nullable', Rule::in(['yes', 'no'])], 'registered_from' => ['nullable', 'date_format:Y-m-d'], 'registered_to' => ['nullable', 'date_format:Y-m-d', ...($this->filled('registered_from') ? ['after_or_equal:registered_from'] : [])], 'page' => ['nullable', 'integer', 'min:1']];
    }
}
