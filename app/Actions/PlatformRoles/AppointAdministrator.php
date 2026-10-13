<?php

namespace App\Actions\PlatformRoles;

use App\Enums\PlatformRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AppointAdministrator
{
    public function __construct(private AssignStaffRole $assignStaffRole) {}

    public function handle(User $user, User $actor): User
    {
        return $this->assignStaffRole->handle($user, $actor, Role::query()->where('name', PlatformRole::Administrator->value)->where('guard_name', 'web')->firstOrFail());
    }
}
