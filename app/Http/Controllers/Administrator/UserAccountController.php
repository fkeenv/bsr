<?php

namespace App\Http\Controllers\Administrator;

use App\Actions\PlatformRoles\AssignStaffRole;
use App\Actions\PlatformRoles\RevokeStaffRole;
use App\Actions\UserAccounts\ChangeUserAccountMembershipRole;
use App\Actions\UserAccounts\EndUserAccountMembership;
use App\Actions\UserAccounts\ListUserAccounts;
use App\Actions\UserAccounts\ShowUserAccount;
use App\Enums\MembershipRole;
use App\Http\Requests\Administrator\StaffRoleAssignmentRequest;
use App\Http\Requests\Administrator\UserAccountIndexRequest;
use App\Http\Requests\Administrator\UserAccountMembershipRequest;
use App\Models\Membership;
use App\Models\User;
use App\Support\FlashToast;
use App\Support\StaffRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserAccountController
{
    public function index(UserAccountIndexRequest $request, ListUserAccounts $listAccounts): Response
    {
        return Inertia::render('administrator/user-accounts/Index', [...$listAccounts->handle($request->validated()),
            'values' => $request->only(['search', 'staff_role', 'membership_role', 'live_membership', 'registered_from', 'registered_to']),
            'staff_roles' => StaffRoles::query()->orderBy('name')->pluck('name')->prepend('super-admin')->all()]);
    }

    public function show(Request $request, User $user, ShowUserAccount $showAccount): Response
    {
        return Inertia::render('administrator/user-accounts/Show', $showAccount->handle($user, $request->user()));
    }

    public function assignRole(StaffRoleAssignmentRequest $request, User $user, Role $role, AssignStaffRole $assignRole): RedirectResponse
    {
        $assignRole->handle($user, $request->user(), $role);
        FlashToast::success('Staff role assigned.');

        return redirect()->route('administrator.user-accounts.show', $user);
    }

    public function revokeRole(StaffRoleAssignmentRequest $request, User $user, Role $role, RevokeStaffRole $revokeRole): RedirectResponse
    {
        $revokeRole->handle($user, $request->user(), $role);
        FlashToast::success('Staff role revoked.');

        return redirect()->route('administrator.user-accounts.show', $user);
    }

    public function endMembership(UserAccountMembershipRequest $request, User $user, Membership $membership, EndUserAccountMembership $endMembership): RedirectResponse
    {
        $endMembership->handle($user, $membership, $request->user(), $request->validated('end_reason'), $request->boolean('confirm_staff_access_loss'));
        FlashToast::success('Property Membership ended.');

        return redirect()->route('administrator.user-accounts.show', $user);
    }

    public function changeMembershipRole(UserAccountMembershipRequest $request, User $user, Membership $membership, ChangeUserAccountMembershipRole $changeRole): RedirectResponse
    {
        $changeRole->handle($user, $membership, $request->user(), MembershipRole::from($request->validated('role')), $request->boolean('confirm_staff_access_loss'));
        FlashToast::success('Property Membership role updated.');

        return redirect()->route('administrator.user-accounts.show', $user);
    }
}
