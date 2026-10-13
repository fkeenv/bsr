<?php

namespace App\Actions\UserAccounts;

use App\Data\StaffRoleOptionData;
use App\Data\UserAccountData;
use App\Data\UserAccountMembershipData;
use App\Enums\HouseholdPermission;
use App\Enums\MembershipRole;
use App\Enums\PlatformRole;
use App\Models\Membership;
use App\Models\User;
use App\Support\PlatformAccess;
use App\Support\StaffRoleAssignments;
use App\Support\StaffRoles;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class ShowUserAccount
{
    /**
     * @return array{account:UserAccountData, ended_memberships:list<UserAccountMembershipData>, staff_role_options:array<int,StaffRoleOptionData>, can_end_memberships:bool, can_change_membership_roles:bool, staff_access_loss_membership_ids:list<int>}
     */
    public function handle(User $subject, User $actor): array
    {
        abort_unless($actor->canAccessAdministratorSurfaces(), 403);
        $subject->load(['roles', 'memberships' => fn ($query) => $query->with(['property', 'endedBy'])->orderByDesc('started_at')->orderByDesc('id')]);
        $account = UserAccountData::fromModel($subject);
        $owners = $subject->memberships->filter(fn (Membership $membership): bool => $membership->isLive() && $membership->role === MembershipRole::Owner);
        $lossIds = ! $account->is_super_admin && $account->staff_roles !== [] && $owners->count() === 1
            ? array_values($owners->pluck('id')->all()) : [];
        $options = StaffRoles::query()->orderBy('name')->get()->map(function (Role $role) use ($subject, $actor, $account): StaffRoleOptionData {
            $assigned = $subject->roles->contains('id', $role->id);
            $canManage = StaffRoleAssignments::canManage($actor, $subject, $role)
                && ! ($account->is_super_admin && ! $assigned && in_array($role->name, [PlatformRole::Officer->value, PlatformRole::Administrator->value], true));

            return new StaffRoleOptionData($role->name, Str::headline($role->name), $assigned, $canManage);
        });

        return [
            'account' => $account,
            'ended_memberships' => array_values($subject->memberships->filter(fn (Membership $membership): bool => ! $membership->isLive())->map(fn (Membership $membership): UserAccountMembershipData => UserAccountMembershipData::fromModel($membership))->all()),
            'staff_role_options' => $options->all(),
            'can_end_memberships' => $actor->id !== $subject->id && PlatformAccess::allowsStaff($actor, HouseholdPermission::MembershipsEnd),
            'can_change_membership_roles' => $actor->id !== $subject->id && PlatformAccess::allowsStaff($actor, HouseholdPermission::MembershipsChangeRole),
            'staff_access_loss_membership_ids' => $lossIds,
        ];
    }
}
