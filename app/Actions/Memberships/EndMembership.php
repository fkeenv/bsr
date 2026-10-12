<?php

namespace App\Actions\Memberships;

use App\Models\Membership;
use App\Models\User;
use App\Support\MembershipAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EndMembership
{
    public function __construct(
        private SyncMemberPlatformRole $syncMemberPlatformRole,
        private RecordMembershipChange $recordMembershipChange,
    ) {}

    public function handle(Membership $membership, User $actor, ?string $reason = null): Membership
    {
        return DB::transaction(function () use ($membership, $actor, $reason): Membership {
            $subject = User::query()->lockForUpdate()->findOrFail($membership->user_id);
            $lockedMembership = Membership::query()->lockForUpdate()->findOrFail($membership->id);
            $lockedMembership->setRelation('user', $subject);
            abort_unless(MembershipAccess::canEnd($actor, $lockedMembership), 403);

            if (! $lockedMembership->isLive()) {
                throw ValidationException::withMessages(['membership' => 'This Membership has already ended.']);
            }

            $isSelf = $actor->id === $lockedMembership->user_id;
            if (! $isSelf && ($reason === null || trim($reason) === '')) {
                throw ValidationException::withMessages(['end_reason' => 'An Officer ending a Membership must leave a reason.']);
            }

            $before = $this->recordMembershipChange->snapshot($lockedMembership);
            $lockedMembership->fill([
                'ended_at' => now(),
                'ended_by_user_id' => $actor->id,
                'end_reason' => $this->nullableString($reason),
            ])->save();
            $this->syncMemberPlatformRole->handle($subject);
            $actor->unsetRelation('roles');
            $this->recordMembershipChange->handle($lockedMembership, $actor, 'end', $before);

            return $lockedMembership;
        });
    }

    private function nullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
