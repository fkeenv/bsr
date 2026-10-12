<?php

namespace App\Actions\PropertyProfiles;

use App\Enums\HouseholdPermission;
use App\Models\Property;
use App\Models\User;
use App\Support\PlatformAccess;
use Illuminate\Auth\Access\Response;

class AuthorizePropertyProfile
{
    public function handle(User $user, Property $property, bool $editing = false): Response
    {
        if (PlatformAccess::allowsStaff($user, HouseholdPermission::ProfilesEdit)
            || (! $editing && PlatformAccess::allowsStaff($user, HouseholdPermission::ProfilesRead))) {
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
