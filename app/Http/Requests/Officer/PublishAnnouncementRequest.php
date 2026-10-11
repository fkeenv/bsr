<?php

namespace App\Http\Requests\Officer;

use App\Enums\AnnouncementPermission;
use App\Support\PlatformAccess;
use Illuminate\Foundation\Http\FormRequest;

class PublishAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return PlatformAccess::allowsAnnouncement($this->user(), AnnouncementPermission::Publish);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
