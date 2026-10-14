<?php

namespace App\Http\Requests\Officer;

use App\Enums\EventPermission;
use App\Support\EventDetails;
use App\Support\PlatformAccess;
use Illuminate\Foundation\Http\FormRequest;

class SaveEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return PlatformAccess::allowsStaff($this->user(), EventPermission::Manage);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return EventDetails::rules();
    }
}
