<?php

namespace App\Http\Requests\Officer;

use App\Enums\AnnouncementPermission;
use App\Support\PlatformAccess;
use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementAttachmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return PlatformAccess::allowsAnnouncement($this->user(), AnnouncementPermission::Attachments);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'attachments' => ['required', 'array', 'min:1'],
            'attachments.*' => [
                'file',
                'max:10240',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf',
            ],
        ];
    }
}
