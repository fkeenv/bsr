<?php

namespace App\Actions\PlatformRoles;

use App\Actions\Memberships\SyncMemberPlatformRole;
use App\Actions\Onboarding\InitializeOnboarding;
use App\Enums\OnboardingExperience;
use App\Enums\PlatformRole;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AppointOfficer
{
    public function __construct(
        private SyncMemberPlatformRole $syncMemberPlatformRole,
        private InitializeOnboarding $initializeOnboarding,
    ) {}

    public function handle(User $user): User
    {
        if ($user->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'user_id' => 'Super Admin accounts cannot be appointed as Officer.',
            ]);
        }

        if (! $user->hasLiveOwnerMembership()) {
            throw ValidationException::withMessages([
                'user_id' => 'Officer may only be appointed to a user with a live owner Membership.',
            ]);
        }

        $hadOfficerAccess = $user->canAccessOfficerSurfaces();

        $user->assignPlatformRole(PlatformRole::Officer);
        $this->syncMemberPlatformRole->handle($user);

        if (! $hadOfficerAccess) {
            $this->initializeOnboarding->handle($user, OnboardingExperience::Officer);
        }

        return $user->fresh() ?? $user;
    }
}
