<?php

namespace App\Support;

use App\Enums\EventPermission;
use App\Models\EventRegistration;
use App\Models\User;

class EventRegistrationAccess
{
    public function ensureCanRead(EventRegistration $registration, ?User $user): void
    {
        abort_unless($user !== null && ($registration->user_id === $user->id
            || PlatformAccess::allowsStaff($user, EventPermission::RegistrationsRead)), 404);
    }

    public function ensureOwner(EventRegistration $registration, ?User $user): void
    {
        abort_unless($user !== null && $registration->user_id === $user->id, 404);
    }
}
