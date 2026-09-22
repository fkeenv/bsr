<?php

namespace App\Actions\PropertyProfiles;

use App\Models\Property;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AuthorizePropertyProfile
{
    public function handle(User $user, Property $property): Response
    {
        if ($user->canAccessOfficerSurfaces()) {
            return Response::allow();
        }

        $holdsLiveMembership = $user->memberships()
            ->live()
            ->whereBelongsTo($property)
            ->exists();

        return $holdsLiveMembership
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
