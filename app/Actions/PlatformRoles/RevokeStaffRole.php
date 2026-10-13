<?php

namespace App\Actions\PlatformRoles;

use App\Models\User;
use App\Support\StaffRoleAssignments;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RevokeStaffRole
{
    public function __construct(private RecordStaffRoleAssignmentChange $recordChange) {}

    public function handle(User $subject, User $actor, Role $role): User
    {
        return DB::transaction(function () use ($subject, $actor, $role): User {
            $subject = User::query()->lockForUpdate()->findOrFail($subject->id);
            $role = Role::query()->lockForUpdate()->findOrFail($role->id);
            abort_unless(StaffRoleAssignments::canManage($actor, $subject, $role), 403);
            $before = StaffRoleAssignments::snapshot($subject);
            $subject->removeRole($role);
            $this->recordChange->handle($actor, $subject, 'revoke', $before);

            return $subject;
        });
    }
}
