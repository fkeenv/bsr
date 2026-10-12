<?php

namespace App\Support;

use App\Enums\HouseholdPermission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class StaffWorkspace
{
    public static function redirect(?User $user, HouseholdPermission $readPermission, string $indexRoute): RedirectResponse
    {
        return redirect()->route(PlatformAccess::allowsStaff($user, $readPermission) ? $indexRoute : 'dashboard');
    }
}
