<?php

namespace App\Actions\UserAccounts;

use App\Actions\Memberships\EndMembership;
use App\Models\Membership;
use App\Models\User;
use App\Support\UserAccountMembershipAccess;
use Illuminate\Support\Facades\DB;

class EndUserAccountMembership
{
    public function __construct(private EndMembership $endMembership) {}

    public function handle(User $subject, Membership $membership, User $actor, string $reason, bool $confirmed): Membership
    {
        return DB::transaction(function () use ($subject, $membership, $actor, $reason, $confirmed): Membership {
            $subject = User::query()->lockForUpdate()->findOrFail($subject->id);
            $membership = Membership::query()->lockForUpdate()->findOrFail($membership->id);
            abort_unless($membership->user_id === $subject->id, 404);
            $membership->setRelation('user', $subject);
            abort_unless(UserAccountMembershipAccess::canManage($actor, $membership, true), 403);
            UserAccountMembershipAccess::requireConfirmation($membership, $confirmed);

            return $this->endMembership->handle($membership, $actor, $reason);
        });
    }
}
