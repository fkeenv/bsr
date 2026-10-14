<?php

namespace App\Support;

use App\Enums\EventPermission;
use App\Models\EventRegistration;
use App\Models\User;

class EventFinancialAccess
{
    public function canRead(EventRegistration $registration, User $user): bool
    {
        return $registration->user_id === $user->id || PlatformAccess::allowsStaff($user, EventPermission::PaymentsReview)
            || PlatformAccess::allowsStaff($user, EventPermission::RefundsReview);
    }

    public function ensureCanRead(EventRegistration $registration, User $user): void
    {
        abort_unless($this->canRead($registration, $user), 404);
    }
}
