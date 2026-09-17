<?php

namespace App\Actions\Announcements;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PinAnnouncement
{
    public const int MaxPinned = 3;

    public function handle(Announcement $announcement, User $officer): Announcement
    {
        if (! $announcement->isPublished()) {
            throw ValidationException::withMessages([
                'pin' => 'Only published Announcements can be pinned.',
            ]);
        }

        if ($announcement->isPinned()) {
            return $announcement;
        }

        $pinnedCount = Announcement::query()
            ->published()
            ->whereNotNull('pinned_at')
            ->count();

        if ($pinnedCount >= self::MaxPinned) {
            throw ValidationException::withMessages([
                'pin' => 'At most three published Announcements can be pinned.',
            ]);
        }

        $announcement->update([
            'pinned_at' => now(),
            'updated_by_user_id' => $officer->id,
        ]);

        return $announcement->refresh();
    }
}
