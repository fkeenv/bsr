<?php

namespace App\Data;

use App\Models\EventRegistration;
use App\Support\EventRegistrationRules;
use Spatie\LaravelData\Data;

class EventRegistrationData extends Data
{
    /** @param list<EventTicketData> $tickets */
    public function __construct(
        public int $id,
        public string $account_name,
        public EventData $event,
        public int $attendee_count,
        public ?string $confirmed_at,
        public ?string $cancelled_at,
        public array $tickets,
    ) {}

    public static function fromModel(EventRegistration $registration, bool $includeTickets = true): self
    {
        $registration->loadMissing(['event', 'user']);
        $tickets = [];
        if ($includeTickets) {
            $registration->loadMissing('attendees');
            $rules = new EventRegistrationRules;
            $tickets = array_values($registration->attendees
                ->sortBy('id')->map(function ($attendee) use ($registration, $rules): EventTicketData {
                    $attendee->setRelation('registration', $registration);

                    return EventTicketData::fromModel($attendee, $rules);
                })->all());
        }

        return new self($registration->id, $registration->user->name, EventData::fromModel($registration->event, false),
            $registration->attendee_count, $registration->confirmed_at?->toIso8601String(),
            $registration->cancelled_at?->toIso8601String(), $tickets);
    }
}
