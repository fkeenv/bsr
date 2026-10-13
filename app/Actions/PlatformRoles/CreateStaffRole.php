<?php

namespace App\Actions\PlatformRoles;

use App\Models\User;
use App\Support\StaffRoles;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class CreateStaffRole
{
    public function __construct(private RecordRoleDefinitionChange $recordChange) {}

    public function handle(User $actor, string $name): Role
    {
        abort_unless($actor->isSuperAdmin(), 403);

        return DB::transaction(function () use ($actor, $name): Role {
            $name = StaffRoles::validatedName($name);
            $role = Role::query()->create(['name' => $name, 'guard_name' => 'web']);
            $this->recordChange->handle($actor, $role, 'create', null, ['name' => $name, 'grants' => []]);

            return $role;
        });
    }
}
