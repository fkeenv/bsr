<?php

namespace App\Actions\Memberships;

use App\Enums\MembershipRole;
use App\Models\Membership;
use App\Models\User;
use App\Support\MembershipAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeMembershipRole
{
    public function __construct(
        private SyncMemberPlatformRole $syncMemberPlatformRole,
        private RecordMembershipChange $recordMembershipChange,
    ) {}

    public function handle(Membership $membership, MembershipRole $role, User $actor): Membership
    {
        return DB::transaction(function () use ($membership, $role, $actor): Membership {
            $subject = User::query()->lockForUpdate()->findOrFail($membership->user_id);
            $lockedMembership = Membership::query()->lockForUpdate()->findOrFail($membership->id);
            $lockedMembership->setRelation('user', $subject);
            abort_unless(MembershipAccess::canChangeRole($actor, $lockedMembership), 403);

            if (! $lockedMembership->isLive()) {
                throw ValidationException::withMessages(['membership' => 'Only a live Membership role can be changed.']);
            }

            if ($lockedMembership->role === $role) {
                return $lockedMembership;
            }

            $before = $this->recordMembershipChange->snapshot($lockedMembership);
            $lockedMembership->fill(['role' => $role])->save();
            $this->syncMemberPlatformRole->handle($subject);
            $actor->unsetRelation('roles');
            $this->recordMembershipChange->handle($lockedMembership, $actor, 'change-role', $before);

            return $lockedMembership;
        });
    }
}
