<?php

namespace App\Actions\PlatformRoles;

use App\Models\User;
use App\Support\StaffRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DeleteStaffRole
{
    public function __construct(private RecordRoleDefinitionChange $recordChange) {}

    public function handle(User $actor, Role $role): void
    {
        abort_unless($actor->isSuperAdmin(), 403);

        DB::transaction(function () use ($actor, $role): void {
            $lockedRole = Role::query()->lockForUpdate()->findOrFail($role->id);
            abort_unless(StaffRoles::isCustom($lockedRole), 403);
            if ($lockedRole->users()->exists()) {
                throw ValidationException::withMessages(['role' => 'Remove all User Account assignments before deleting this role.']);
            }
            $grants = array_values($lockedRole->permissions->map(fn (Permission $permission): string => $permission->name)->sort()->all());
            $this->recordChange->handle($actor, $lockedRole, 'delete', ['name' => $lockedRole->name, 'grants' => $grants], null);
            $lockedRole->delete();
        });
    }
}
