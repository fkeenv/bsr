<?php

namespace App\Support;

use App\Enums\AnnouncementVisibility;
use App\Models\Announcement;
use App\Models\User;

class AnnouncementAccess
{
    /** @return list<AnnouncementVisibility> */
    public function visibleVisibilities(?User $user): array
    {
        if ($user?->canAccessOfficerSurfaces()) {
            return AnnouncementVisibility::ordered();
        }

        if ($user?->hasLiveMembership()) {
            return [AnnouncementVisibility::Public, AnnouncementVisibility::Private];
        }

        return [AnnouncementVisibility::Public];
    }

    public function ensureCanView(Announcement $announcement, ?User $user): void
    {
        if ($user?->canAccessOfficerSurfaces()) {
            return;
        }

        abort_unless($announcement->isPublished()
            && in_array($announcement->visibility, $this->visibleVisibilities($user), true), 404);
    }
}
