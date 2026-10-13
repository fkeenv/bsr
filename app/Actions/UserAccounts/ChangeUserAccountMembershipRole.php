<?php

namespace App\Actions\UserAccounts;

use App\Actions\Memberships\ChangeMembershipRole;
use App\Enums\MembershipRole;
use App\Models\Membership;
use App\Models\User;
use App\Support\UserAccountMembershipAccess;
use Illuminate\Support\Facades\DB;

class ChangeUserAccountMembershipRole
{
    public function __construct(private ChangeMembershipRole $changeMembershipRole) {}

    public function handle(User $subject, Membership $membership, User $actor, MembershipRole $role, bool $confirmed): Membership
    {
        return DB::transaction(function () use ($subject, $membership, $actor, $role, $confirmed): Membership {
            $subject = User::query()->lockForUpdate()->findOrFail($subject->id);
            $membership = Membership::query()->lockForUpdate()->findOrFail($membership->id);
            abort_unless($membership->user_id === $subject->id, 404);
            $membership->setRelation('user', $subject);
            abort_unless(UserAccountMembershipAccess::canManage($actor, $membership, false), 403);
            if ($role === MembershipRole::Resident) {
                UserAccountMembershipAccess::requireConfirmation($membership, $confirmed);
            }

            return $this->changeMembershipRole->handle($membership, $role, $actor);
        });
    }
}
