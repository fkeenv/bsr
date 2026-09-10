<?php

namespace App\Actions\PlatformRoles;

use App\Data\PlatformRoleCandidateData;
use App\Enums\MembershipRole;
use App\Enums\PlatformRole;
use App\Models\Membership;
use App\Models\User;

class ListPlatformRoleAppointmentPage
{
    /**
     * @return array{candidates: array<int, PlatformRoleCandidateData>, holders: array<int, PlatformRoleCandidateData>}
     */
    public function handle(PlatformRole $role): array
    {
        $ownerUserIds = Membership::query()
            ->live()
            ->where('role', MembershipRole::Owner)
            ->pluck('user_id')
            ->unique()
            ->values();

        $candidates = User::query()
            ->whereIn('id', $ownerUserIds)
            ->orderBy('name')
            ->get()
            ->reject(fn (User $user): bool => $user->isSuperAdmin())
            ->map(fn (User $user): PlatformRoleCandidateData => PlatformRoleCandidateData::fromModel($user))
            ->values()
            ->all();

        $holders = User::role($role->value)
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): PlatformRoleCandidateData => PlatformRoleCandidateData::fromModel($user))
            ->values()
            ->all();

        return [
            'candidates' => $candidates,
            'holders' => $holders,
        ];
    }
}
