<?php

namespace App\Actions\Announcements;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PublishAnnouncement
{
    public function handle(Announcement $announcement, User $officer): Announcement
    {
        if ($announcement->isPublished()) {
            throw ValidationException::withMessages([
                'publish' => 'This Announcement is already published.',
            ]);
        }

        $announcement->update([
            'published_at' => now(),
            'updated_by_user_id' => $officer->id,
        ]);

        return $announcement->refresh();
    }
}
