<?php

namespace App\Http\Requests\Officer;

use App\Enums\EventPermission;
use App\Support\EventDetails;
use App\Support\PlatformAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class RescheduleEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return PlatformAccess::allowsStaff($this->user(), EventPermission::Manage);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return Arr::only(EventDetails::rules(), ['starts_at', 'ends_at', 'registration_deadline']);
    }
}
