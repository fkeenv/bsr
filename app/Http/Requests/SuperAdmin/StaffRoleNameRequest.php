<?php

namespace App\Http\Requests\SuperAdmin;

use App\Support\StaffRoles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class StaffRoleNameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => Str::slug($this->input('name'))]);
        }
    }

    /** @return array{name: list<mixed>} */
    public function rules(): array
    {
        $role = $this->route('role');

        return ['name' => StaffRoles::nameRules($role instanceof Role ? $role : null)];
    }
}
