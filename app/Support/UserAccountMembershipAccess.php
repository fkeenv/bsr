<?php

namespace App\Support;

use App\Enums\MembershipRole;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UserAccountMembershipAccess
{
    public static function losesStaffAccess(Membership $membership): bool
    {
        $subject = $membership->user;

        return $membership->isLive() && $membership->role === MembershipRole::Owner && ! $subject->isSuperAdmin()
            && StaffRoleAssignments::snapshot($subject) !== []
            && ! $subject->memberships()->live()->where('role', MembershipRole::Owner)->where('id', '!=', $membership->id)->exists();
    }

    public static function requireConfirmation(Membership $membership, bool $confirmed): void
    {
        if (self::losesStaffAccess($membership) && ! $confirmed) {
            throw ValidationException::withMessages(['confirm_staff_access_loss' => 'Confirm that this change will remove all staff roles and staff access.']);
        }
    }

    public static function canManage(?User $actor, Membership $membership, bool $end): bool
    {
        return $actor !== null && $actor->canAccessAdministratorSurfaces() && $actor->id !== $membership->user_id
            && ($end ? MembershipAccess::canEnd($actor, $membership) : MembershipAccess::canChangeRole($actor, $membership));
    }
}
