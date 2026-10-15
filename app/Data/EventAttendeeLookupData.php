<?php

namespace App\Data;

use App\Models\EventAttendee;
use App\Support\EventRegistrationRules;
use Spatie\LaravelData\Data;

class EventAttendeeLookupData extends Data
{
    public function __construct(public int $id, public string $name, public string $account_name,
        public string $status_label, public bool $valid, public ?string $ticket,
        public ?string $checked_in_at, public ?int $checked_in_by_user_id) {}

    public static function fromModel(EventAttendee $attendee, EventRegistrationRules $rules): self
    {
        return new self($attendee->id, $attendee->name, $attendee->registration->user->name,
            $rules->effectiveStatus($attendee->registration)->label(), $rules->ticketIsValid($attendee),
            $attendee->ticket_token, $attendee->checked_in_at?->toIso8601String(), $attendee->checked_in_by_user_id);
    }
}
