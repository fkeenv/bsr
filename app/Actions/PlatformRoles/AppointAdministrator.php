<?php

namespace App\Actions\PlatformRoles;

use App\Actions\Memberships\SyncMemberPlatformRole;
use App\Enums\PlatformRole;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AppointAdministrator
{
    public function __construct(private SyncMemberPlatformRole $syncMemberPlatformRole) {}

    public function handle(User $user): User
    {
        if ($user->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'user_id' => 'Super Admin accounts cannot be appointed as Administrator.',
            ]);
        }

        if (! $user->hasLiveOwnerMembership()) {
            throw ValidationException::withMessages([
                'user_id' => 'Administrator may only be appointed to a user with a live owner Membership.',
            ]);
        }

        $user->assignPlatformRole(PlatformRole::Administrator);
        $this->syncMemberPlatformRole->handle($user);

        return $user->fresh() ?? $user;
    }
}
