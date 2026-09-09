<?php

namespace App\Actions\Memberships;

use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EndMembership
{
    public function __construct(private SyncMemberPlatformRole $syncMemberPlatformRole) {}

    public function handle(Membership $membership, User $actor, ?string $reason = null): Membership
    {
        if (! $membership->isLive()) {
            throw ValidationException::withMessages([
                'membership' => 'This Membership has already ended.',
            ]);
        }

        $isSelf = $actor->id === $membership->user_id;
        $isOfficer = $actor->canAccessOfficerSurfaces();

        if (! $isSelf && ! $isOfficer) {
            throw ValidationException::withMessages([
                'membership' => 'You are not allowed to end this Membership.',
            ]);
        }

        if (! $isSelf && ($reason === null || trim($reason) === '')) {
            throw ValidationException::withMessages([
                'end_reason' => 'An Officer ending a Membership must leave a reason.',
            ]);
        }

        return DB::transaction(function () use ($membership, $actor, $reason, $isSelf): Membership {
            $membership->fill([
                'ended_at' => now(),
                'ended_by_user_id' => $actor->id,
                'end_reason' => $isSelf ? $this->nullableString($reason) : trim((string) $reason),
            ])->save();

            $this->syncMemberPlatformRole->handle($membership->user);

            return $membership->fresh() ?? $membership;
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
