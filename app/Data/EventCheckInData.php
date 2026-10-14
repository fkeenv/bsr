<?php

namespace App\Data;

use App\Models\EventAttendee;
use Spatie\LaravelData\Data;

class EventCheckInData extends Data
{
    public function __construct(public string $outcome, public string $name, public string $ticket,
        public string $checked_in_at, public int $checked_in_by_user_id) {}

    public static function fromModel(EventAttendee $attendee, bool $alreadyCheckedIn): self
    {
        if ($attendee->ticket_token === null || $attendee->checked_in_at === null || $attendee->checked_in_by_user_id === null) {
            throw new \LogicException('A check-in result requires a recorded admission.');
        }

        return new self($alreadyCheckedIn ? 'already-checked-in' : 'checked-in', $attendee->name,
            $attendee->ticket_token ?? '', $attendee->checked_in_at->toIso8601String(), $attendee->checked_in_by_user_id);
    }
}
