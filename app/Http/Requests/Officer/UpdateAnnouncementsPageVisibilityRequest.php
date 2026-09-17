<?php

namespace App\Http\Requests\Officer;

use App\Enums\AnnouncementsPageVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnnouncementsPageVisibilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessOfficerSurfaces() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'announcements_page_visibility' => [
                'required',
                Rule::enum(AnnouncementsPageVisibility::class),
            ],
        ];
    }
}
