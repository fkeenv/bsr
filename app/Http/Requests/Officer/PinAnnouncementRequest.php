<?php

namespace App\Http\Requests\Officer;

use App\Enums\AnnouncementPermission;
use App\Support\PlatformAccess;
use Illuminate\Foundation\Http\FormRequest;

class PinAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return PlatformAccess::allowsAnnouncement($this->user(), AnnouncementPermission::Pin);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
