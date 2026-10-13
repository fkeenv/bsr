<?php

namespace App\Http\Requests\Administrator;

use App\Models\User;
use App\Support\StaffRoleAssignments;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\Permission\Models\Role;

class StaffRoleAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subject = $this->route('user');
        $role = $this->route('role');

        return $subject instanceof User && $role instanceof Role && StaffRoleAssignments::canManage($this->user(), $subject, $role);
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [];
    }
}
