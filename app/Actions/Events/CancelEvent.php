<?php

namespace App\Actions\Events;

use App\Enums\EventPermission;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use App\Support\PlatformAccess;
use Illuminate\Support\Facades\DB;

class CancelEvent
{
    public function handle(User $actor, Event $event): void
    {
        abort_unless(PlatformAccess::allowsStaff($actor, EventPermission::Manage), 403);
        DB::transaction(function () use ($actor, $event): void {
            $event = Event::query()->lockForUpdate()->findOrFail($event->id);
            if ($event->status === EventStatus::Cancelled) {
                return;
            }
            $event->update(['status' => EventStatus::Cancelled, 'cancelled_at' => now(), 'updated_by_user_id' => $actor->id]);
        });
    }
}
