<?php

namespace App\Actions\Memberships;

use App\Enums\PlatformRole;
use App\Models\User;
use App\Support\PlatformAccess;
use App\Support\StaffRoles;

class SyncMemberPlatformRole
{
    public function handle(User $user): void
    {
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
        if (PlatformAccess::canHoldStaffRole($user)) {
            return;
        }

        $roles = StaffRoles::query()->whereIn('id', $user->roles()->select('roles.id')->getQuery())->get();
        foreach ($roles as $role) {
            $user->removeRole($role);
        }
    }
}
