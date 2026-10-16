<?php

namespace App\Support;

use App\Enums\EventAudience;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class EventAccess
{
    /** @return Builder<Event> */
    public function visibleQuery(?User $user): Builder
    {
        return Event::query()->whereIn('status', EventStatus::publishedStates())
            ->when(! $user?->hasLiveMembership(), fn (Builder $query) => $query->where('audience', EventAudience::Public));
    }

    public function ensureCanView(Event $event, ?User $user): void
    {
        abort_unless($this->visibleQuery($user)->whereKey($event->id)->exists(), 404);
    }
}
