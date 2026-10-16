<?php

namespace App\Actions\Events;

use App\Data\EventData;
use App\Enums\EventPermission;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use App\Support\PlatformAccess;

class ListCheckInEvents
{
    /** @return list<EventData> */
    public function handle(User $actor): array
    {
        abort_unless(PlatformAccess::allowsStaff($actor, EventPermission::CheckIn), 403);

        return array_values(Event::query()->whereIn('status', EventStatus::publishedStates())->orderByDesc('starts_at')->orderByDesc('id')->get()
            ->map(fn (Event $event): EventData => EventData::fromModel($event, false))->all());
    }
}
