<?php

namespace App\Actions\Memberships;

use App\Enums\MembershipRole;
use App\Models\Membership;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeMembershipRole
{
    public function __construct(private SyncMemberPlatformRole $syncMemberPlatformRole) {}

    public function handle(Membership $membership, MembershipRole $role): Membership
    {
        if (! $membership->isLive()) {
            throw ValidationException::withMessages([
                'membership' => 'Only a live Membership role can be changed.',
            ]);
        }

        return DB::transaction(function () use ($membership, $role): Membership {
            $membership->fill(['role' => $role])->save();
            $this->syncMemberPlatformRole->handle($membership->user);

            return $membership->fresh() ?? $membership;
        });
    }
}
