<?php

namespace App\Http\Requests\SuperAdmin;

use App\Enums\AnnouncementPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'grants' => ['present', 'array'],
            'grants.*' => ['required', 'string', 'distinct', Rule::in(AnnouncementPermission::values())],
        ];
    }
}
