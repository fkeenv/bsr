<?php

namespace App\Data;

use App\Models\EventAttendee;
use App\Support\EventRegistrationRules;
use Spatie\LaravelData\Data;

class EventTicketData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $valid,
        public ?string $checked_in_at,
        public ?string $ticket_url,
        public ?string $qr_url,
    ) {}

    public static function fromModel(EventAttendee $attendee, EventRegistrationRules $rules): self
    {
        $valid = $rules->ticketIsValid($attendee);
        $parameters = ['attendee' => $attendee->ticket_token];

        return new self($attendee->id, $attendee->name, $valid, $attendee->checked_in_at?->toIso8601String(),
            $attendee->ticket_token !== null ? route('event-tickets.show', $parameters) : null, $valid ? route('event-tickets.qr', $parameters) : null);
    }
}
