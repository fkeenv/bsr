<?php

namespace App\Support;

use App\Enums\AnnouncementsPageVisibility;
use App\Models\AssociationSetting;
use App\Models\User;

class AnnouncementsPageAccess
{
    public function visibility(): AnnouncementsPageVisibility
    {
        return AssociationSetting::current()->announcements_page_visibility;
    }

    public function ensureCanViewFeed(?User $user): void
    {
        $visibility = $this->visibility();

        if ($visibility === AnnouncementsPageVisibility::Public) {
            return;
        }

        if ($visibility === AnnouncementsPageVisibility::Hidden) {
            abort_unless($user?->canAccessOfficerSurfaces() ?? false, 404);

            return;
        }

        abort_unless(
            $user !== null && ($user->hasLiveMembership() || $user->canAccessOfficerSurfaces()),
            403,
        );
    }

    public function isListedInMembershipNav(): bool
    {
        return $this->visibility() !== AnnouncementsPageVisibility::Hidden;
    }
}
