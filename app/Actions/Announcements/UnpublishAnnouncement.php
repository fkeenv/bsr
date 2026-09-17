<?php

namespace App\Actions\Announcements;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UnpublishAnnouncement
{
    public function handle(Announcement $announcement, User $officer): Announcement
    {
        if (! $announcement->isPublished()) {
            throw ValidationException::withMessages([
                'unpublish' => 'This Announcement is already a draft.',
            ]);
        }

        $announcement->update([
            'published_at' => null,
            'pinned_at' => null,
            'updated_by_user_id' => $officer->id,
        ]);

        return $announcement->refresh();
    }
}
