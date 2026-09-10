<?php

namespace App\Actions\Memberships;

use App\Enums\PlatformRole;
use App\Models\User;

class SyncMemberPlatformRole
{
    public function handle(User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $hasLiveMembership = $user->hasLiveMembership();

        if ($hasLiveMembership) {
            if (! $user->hasRole(PlatformRole::Member)) {
                $user->assignRole(PlatformRole::Member);
            }
        } else {
            $user->removeRole(PlatformRole::Member);
        }

        $this->syncLeadershipRoles($user);
    }

    private function syncLeadershipRoles(User $user): void
    {
        if ($user->hasLiveOwnerMembership()) {
            return;
        }

        if ($user->hasRole(PlatformRole::Officer)) {
            $user->removeRole(PlatformRole::Officer);
        }

        if ($user->hasRole(PlatformRole::Administrator)) {
            $user->removeRole(PlatformRole::Administrator);
        }
    }
}
