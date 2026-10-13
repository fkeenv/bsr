<?php

namespace App\Actions\PlatformRoles;

use App\Actions\Memberships\SyncMemberPlatformRole;
use App\Actions\Onboarding\InitializeOnboarding;
use App\Enums\OnboardingExperience;
use App\Enums\PlatformRole;
use App\Models\User;
use App\Support\PlatformAccess;
use App\Support\StaffRoleAssignments;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AssignStaffRole
{
    public function __construct(private SyncMemberPlatformRole $syncMemberPlatformRole, private InitializeOnboarding $initializeOnboarding, private RecordStaffRoleAssignmentChange $recordChange) {}

    public function handle(User $subject, User $actor, Role $role): User
    {
        return DB::transaction(function () use ($subject, $actor, $role): User {
            $subject = User::query()->lockForUpdate()->findOrFail($subject->id);
            $role = Role::query()->lockForUpdate()->findOrFail($role->id);
            abort_unless(StaffRoleAssignments::canManage($actor, $subject, $role), 403);
            if ($subject->isSuperAdmin() && in_array($role->name, [PlatformRole::Officer->value, PlatformRole::Administrator->value], true)) {
                throw ValidationException::withMessages(['user_id' => 'Super Admin accounts cannot be appointed to this built-in staff role.']);
            }
            if (! PlatformAccess::canHoldStaffRole($subject)) {
                throw ValidationException::withMessages(['user_id' => 'Staff roles require a live Owner Membership.']);
            }
            $before = StaffRoleAssignments::snapshot($subject);
            $hadStaffAccess = $subject->canAccessOfficerSurfaces();
            $subject->assignRole([$role, PlatformRole::User->value]);
            $this->syncMemberPlatformRole->handle($subject);
            if (! $hadStaffAccess) {
                $this->initializeOnboarding->handle($subject, OnboardingExperience::Officer);
            }
            $this->recordChange->handle($actor, $subject, 'assign', $before);

            return $subject;
        });
    }
}
