<?php

namespace App\Http\Requests\SuperAdmin;

use App\Actions\PlatformRoles\ListAccessHistory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccessHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return ['actor' => ['nullable', 'integer', 'min:1'], 'account' => ['nullable', 'integer', 'min:1'], 'role' => ['nullable', 'string', 'max:64'],
            'action' => ['nullable', Rule::in(array_keys(ListAccessHistory::ACTIONS))],
            'occurred_from' => ['nullable', 'date_format:Y-m-d'], 'occurred_to' => ['nullable', 'date_format:Y-m-d', ...($this->filled('occurred_from') ? ['after_or_equal:occurred_from'] : [])],
            'page' => ['nullable', 'integer', 'min:1']];
    }
}
