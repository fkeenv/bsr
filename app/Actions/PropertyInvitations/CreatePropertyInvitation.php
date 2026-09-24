<?php

namespace App\Actions\PropertyInvitations;

use App\Enums\MembershipRole;
use App\Models\Property;
use App\Models\PropertyInvitation;
use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CreatePropertyInvitation
{
    public function handle(Property $property, MembershipRole $role, User $creator): string
    {
        if (! $property->is_active) {
            throw new InvalidArgumentException('Invitations can only be created for active Properties.');
        }

        $token = Str::random(64);

        PropertyInvitation::query()->create([
            'property_id' => $property->id,
            'role' => $role,
            'created_by_user_id' => $creator->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(30),
        ]);

        return $token;
    }
}
