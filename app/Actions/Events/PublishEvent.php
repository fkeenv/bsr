<?php

namespace App\Actions\Events;

use App\Enums\EventPermission;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use App\Support\PlatformAccess;
use App\Support\SanitizeHtml;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishEvent
{
    public function handle(User $actor, Event $event): void
    {
        abort_unless(PlatformAccess::allowsStaff($actor, EventPermission::Manage), 403);
        DB::transaction(function () use ($actor, $event): void {
            $event = Event::query()->lockForUpdate()->findOrFail($event->id);
            if ($event->status === EventStatus::Cancelled) {
                throw ValidationException::withMessages(['event' => 'Cancelled Events cannot be published.']);
            }
            if ($event->status === EventStatus::Published) {
                return;
            }
            if (blank($event->title) || blank($event->venue) || SanitizeHtml::isBlank($event->description)
                || ! $event->starts_at->isFuture() || ! $event->ends_at->greaterThan($event->starts_at)
                || ! $event->registration_deadline->isFuture() || $event->registration_deadline->greaterThan($event->starts_at)
                || ($event->capacity !== null && $event->capacity < 0) || $event->price_centavos !== 0) {
                throw ValidationException::withMessages(['event' => 'Provide valid Event details and a future schedule and deadline before publishing a free Event.']);
            }
            $event->update(['status' => EventStatus::Published, 'published_at' => now(), 'updated_by_user_id' => $actor->id]);
        });
    }
}
