<?php

namespace App\Http\Requests\Officer;

use App\Enums\FinancialPermission;
use App\Support\PlatformAccess;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLevySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return PlatformAccess::allowsStaff($this->user(), FinancialPermission::LevySettingsEdit);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'levy_day_of_month' => ['required', 'integer', 'min:1', 'max:31'],
        ];
    }
}
