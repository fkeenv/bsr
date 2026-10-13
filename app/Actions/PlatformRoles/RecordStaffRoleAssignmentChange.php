<?php

namespace App\Actions\PlatformRoles;

use App\Models\StaffRoleAssignmentChange;
use App\Models\User;
use App\Support\StaffRoleAssignments;

class RecordStaffRoleAssignmentChange
{
    /** @param list<string> $previousAssignments */
    public function handle(User $actor, User $subject, string $action, array $previousAssignments, ?string $reason = null, ?int $membershipId = null): void
    {
        $newAssignments = StaffRoleAssignments::snapshot($subject);
        if ($previousAssignments === $newAssignments) {
            return;
        }
        StaffRoleAssignmentChange::query()->create(['actor_user_id' => $actor->id, 'user_id' => $subject->id, 'membership_id' => $membershipId,
            'action' => $action, 'previous_assignments' => $previousAssignments, 'new_assignments' => $newAssignments, 'reason' => $reason]);
    }
}
