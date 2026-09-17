<?php

namespace App\Actions\Announcements;

use App\Models\Announcement;
use App\Models\User;

class UnpinAnnouncement
{
    public function handle(Announcement $announcement, User $officer): Announcement
    {
        if (! $announcement->isPinned()) {
            return $announcement;
        }

        $announcement->update([
            'pinned_at' => null,
            'updated_by_user_id' => $officer->id,
        ]);

        return $announcement->refresh();
    }
}
