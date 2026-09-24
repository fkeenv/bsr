<?php

namespace App\Actions\PropertyInvitations;

use App\Models\PropertyInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RevokePropertyInvitation
{
    public function handle(PropertyInvitation $invitation, User $revoker): PropertyInvitation
    {
        return DB::transaction(function () use ($invitation, $revoker): PropertyInvitation {
            $lockedInvitation = PropertyInvitation::query()
                ->lockForUpdate()
                ->findOrFail($invitation->id);

            if (! $lockedInvitation->canBeRevoked()) {
                throw new InvalidArgumentException('Only unused invitations can be revoked.');
            }

            $lockedInvitation->update([
                'revoked_at' => now(),
                'revoked_by_user_id' => $revoker->id,
            ]);

            return $lockedInvitation;
        });
    }
}
