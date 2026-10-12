<?php

namespace App\Http\Requests\Officer;

use App\Enums\HouseholdPermission;
use App\Support\PlatformAccess;
use Illuminate\Foundation\Http\FormRequest;

class ImportPropertiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return PlatformAccess::allowsStaff($this->user(), HouseholdPermission::PropertiesImport);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }
}
