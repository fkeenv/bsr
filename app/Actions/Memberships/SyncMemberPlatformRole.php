<?php

namespace App\Actions\Memberships;

use App\Enums\MembershipRole;
use App\Enums\PlatformRole;
use App\Models\Membership;
use App\Models\User;

class SyncMemberPlatformRole
{
    public function handle(User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $hasLiveMembership = $user->memberships()->live()->exists();

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
        $hasLiveOwnerMembership = Membership::query()
            ->live()
            ->where('user_id', $user->id)
            ->where('role', MembershipRole::Owner)
            ->exists();

        if ($hasLiveOwnerMembership) {
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
