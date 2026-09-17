<?php

namespace App\Actions\Announcements;

use App\Models\Announcement;
use App\Models\User;
use App\Support\SanitizeHtml;
use Illuminate\Validation\ValidationException;

class CreateAnnouncementDraft
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $officer, array $data): Announcement
    {
        $title = $data['title'] ?? null;
        $bodyHtml = $data['body'] ?? null;

        if (! is_string($title) || trim($title) === '') {
            throw ValidationException::withMessages([
                'title' => 'The Announcement title is required.',
            ]);
        }

        if (! is_string($bodyHtml)) {
            throw ValidationException::withMessages([
                'body' => 'The Announcement body is required.',
            ]);
        }

        $body = SanitizeHtml::announcement($bodyHtml);

        if (SanitizeHtml::isBlank($body)) {
            throw ValidationException::withMessages([
                'body' => 'The Announcement body is required.',
            ]);
        }

        return Announcement::query()->create([
            'title' => trim($title),
            'body' => $body,
            'created_by_user_id' => $officer->id,
            'updated_by_user_id' => $officer->id,
        ]);
    }
}
