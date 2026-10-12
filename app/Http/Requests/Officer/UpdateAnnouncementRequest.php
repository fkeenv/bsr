<?php

namespace App\Http\Requests\Officer;

use App\Enums\AnnouncementPermission;
use App\Enums\AnnouncementVisibility;
use App\Support\PlatformAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return PlatformAccess::allowsAnnouncement($this->user(), AnnouncementPermission::Edit);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'visibility' => ['sometimes', 'required', Rule::enum(AnnouncementVisibility::class)],
        ];
    }
}
