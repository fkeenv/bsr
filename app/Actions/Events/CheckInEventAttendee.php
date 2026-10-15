<?php

namespace App\Actions\Events;

use App\Data\EventCheckInData;
use App\Enums\EventPermission;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\User;
use App\Support\EventRegistrationRules;
use App\Support\EventTicketIdentity;
use App\Support\PlatformAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckInEventAttendee
{
    public function __construct(private EventRegistrationRules $rules, private EventTicketIdentity $identity) {}

    public function handle(User $actor, Event $event, string $ticket): EventCheckInData
    {
        abort_unless(PlatformAccess::allowsStaff($actor, EventPermission::CheckIn), 403);
        $token = $this->identity->fromScan($ticket);

        return DB::transaction(function () use ($actor, $event, $token): EventCheckInData {
            $event = Event::query()->lockForUpdate()->findOrFail($event->id);
            $attendee = EventAttendee::query()->where('ticket_token', $token)->whereHas('registration', fn ($query) => $query->where('event_id', $event->id))->lockForUpdate()->first();
            if ($attendee === null) {
                throw ValidationException::withMessages(['ticket' => 'This ticket is unknown or belongs to a different Event.']);
            }
            $attendee->registration->setRelation('event', $event);
            if (! $this->rules->ticketIsValid($attendee)) {
                throw ValidationException::withMessages(['ticket' => 'This ticket is not valid for admission. Only confirmed, active tickets can be checked in.']);
            }
            $alreadyCheckedIn = $attendee->checked_in_at !== null;
            if (! $alreadyCheckedIn) {
                $attendee->update(['checked_in_at' => now(), 'checked_in_by_user_id' => $actor->id]);
            }

            return EventCheckInData::fromModel($attendee, $alreadyCheckedIn);
        }, 5);
    }
}
