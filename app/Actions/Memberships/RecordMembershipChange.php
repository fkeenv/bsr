<?php

namespace App\Actions\Memberships;

use App\Actions\PlatformRoles\RecordStaffRoleAssignmentChange;
use App\Models\Membership;
use App\Models\MembershipChange;
use App\Models\User;
use App\Support\StaffRoleAssignments;

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
    public function __construct(private RecordStaffRoleAssignmentChange $recordStaffRoleChange) {}

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
            'staff_roles' => StaffRoleAssignments::snapshot($membership->user),
        ];
    }

    /** @param MembershipSnapshot $previousState */
    public function handle(Membership $membership, User $actor, string $action, array $previousState): void
    {
        $this->recordStaffRoleChange->handle($actor, $membership->user, 'automatic-removal', $previousState['staff_roles'],
            $action === 'end' ? 'last-owner-membership-ended' : 'last-owner-membership-changed-to-resident', $membership->id);
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
