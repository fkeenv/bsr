<?php

namespace App\Actions\Events;

use App\Data\EventData;
use App\Enums\EventPermission;
use App\Models\Event;
use App\Models\User;
use App\Support\EventAccess;
use App\Support\PlatformAccess;

class ListEvents
{
    public function __construct(private EventAccess $access) {}

    /** @return list<EventData> */
    public function handle(?User $user, bool $management = false): array
    {
        abort_if($management && ! PlatformAccess::allowsStaff($user, EventPermission::Manage), 403);
        $query = $management ? Event::query() : $this->access->visibleQuery($user);

        return array_values($query->orderBy('starts_at')->orderBy('id')->get()
            ->map(fn (Event $event): EventData => EventData::fromModel($event, false))->all());
    }
}
