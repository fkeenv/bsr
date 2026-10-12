<?php

namespace App\Actions\Memberships;

use App\Enums\PlatformRole;
use App\Models\Membership;
use App\Models\MembershipChange;
use App\Models\User;

/**
 * @phpstan-type MembershipSnapshot array{
 *     membership_id: int,
 *     property_id: int,
 *     role: string,
 *     started_at: string,
 *     ended_at: string|null,
 *     ended_by_user_id: int|null,
 *     end_reason: string|null,
 *     staff_roles: list<string>
 * }
 */
class RecordMembershipChange
{
    /** @return MembershipSnapshot */
    public function snapshot(Membership $membership): array
    {
        return [
            'membership_id' => $membership->id,
            'property_id' => $membership->property_id,
            'role' => $membership->role->value,
            'started_at' => $membership->started_at->toIso8601String(),
            'ended_at' => $membership->ended_at?->toIso8601String(),
            'ended_by_user_id' => $membership->ended_by_user_id,
            'end_reason' => $membership->end_reason,
            'staff_roles' => array_values($membership->user->roles()
                ->where('guard_name', 'web')
                ->whereIn('name', [PlatformRole::Officer->value, PlatformRole::Administrator->value])
                ->orderBy('name')->pluck('name')->all()),
        ];
    }

    /** @param MembershipSnapshot $previousState */
    public function handle(Membership $membership, User $actor, string $action, array $previousState): void
    {
        MembershipChange::query()->create([
            'membership_id' => $membership->id,
            'actor_user_id' => $actor->id,
            'user_id' => $membership->user_id,
            'action' => $action,
            'previous_state' => $previousState,
            'new_state' => $this->snapshot($membership),
        ]);
    }
}
