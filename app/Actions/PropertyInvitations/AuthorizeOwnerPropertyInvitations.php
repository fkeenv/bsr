<?php

namespace App\Actions\PropertyInvitations;

use App\Enums\MembershipRole;
use App\Models\Property;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AuthorizeOwnerPropertyInvitations
{
    public function handle(User $user, Property $property): Response
    {
        $ownsProperty = $user->memberships()
            ->live()
            ->whereBelongsTo($property)
            ->where('role', MembershipRole::Owner)
            ->exists();

        return $ownsProperty ? Response::allow() : Response::denyAsNotFound();
    }
}
