<?php

namespace App\Actions\Events;

use App\Data\EventRegistrationData;
use App\Enums\EventPermission;
use App\Models\EventRegistration;
use App\Models\User;
use App\Support\PlatformAccess;

class ListEventRegistrations
{
    /** @return list<EventRegistrationData> */
    public function handle(User $account, bool $staff = false): array
    {
        abort_if($staff && ! PlatformAccess::allowsStaff($account, EventPermission::RegistrationsRead), 403);

        return array_values(EventRegistration::query()->when(! $staff, fn ($query) => $query->where('user_id', $account->id))
            ->with(['event', 'user'])->orderByDesc('id')->get()
            ->map(fn (EventRegistration $registration) => EventRegistrationData::fromModel($registration, false))->all());
    }
}
