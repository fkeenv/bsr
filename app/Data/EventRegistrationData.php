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
        public string $status,
        public string $status_label,
        public int $price_centavos,
        public int $total_centavos,
        public ?string $expires_at,
        public ?string $payment_instructions,
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
            $registration->cancelled_at?->toIso8601String(), $tickets,
            (new EventRegistrationRules)->effectiveStatus($registration)->value, (new EventRegistrationRules)->effectiveStatus($registration)->label(),
            $registration->price_centavos, $registration->total_centavos, $registration->expires_at?->toIso8601String(), $includeTickets ? $registration->payment_instructions : null);
    }
}
