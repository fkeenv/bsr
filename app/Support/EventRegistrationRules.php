<?php

namespace App\Support;

use App\Enums\EventAudience;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class EventRegistrationRules
{
    /** @return array<string, mixed> */
    public static function submissionRules(): array
    {
        return ['submission_key' => ['required', 'uuid'], 'attendees' => ['required', 'array', 'list', 'min:1'],
            'attendees.*' => ['required', 'array:name'], 'attendees.*.name' => self::nameRules(),
            'attendee_count' => ['prohibited'], 'user_id' => ['prohibited']];
    }

    /** @return list<string> */
    public static function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    public function reservedPlaces(Event $event): int
    {
        return (int) $event->registrations()->whereNull('cancelled_at')->sum('attendee_count');
    }

    public function unavailableReason(Event $event, ?User $user): ?string
    {
        if ($event->status !== EventStatus::Published) {
            return 'This Event is not accepting registrations.';
        }
        if ($event->price_centavos !== 0) {
            return 'Paid Event registration is not available yet.';
        }
        if ($event->starts_at->lessThanOrEqualTo(now()) || $event->registration_deadline->lessThanOrEqualTo(now())) {
            return 'Registration for this Event has closed.';
        }
        if ($user === null) {
            return 'Sign in to register for this Event.';
        }
        if ($event->audience === EventAudience::MembersOnly && ! $user->hasLiveMembership()) {
            return 'A live Membership is required to register for this Event.';
        }
        if ($event->capacity !== null && $this->reservedPlaces($event) >= $event->capacity) {
            return 'This Event has no places left.';
        }

        return null;
    }

    public function ensureCanReserve(Event $event, User $user, int $places): void
    {
        $reason = $this->unavailableReason($event, $user);
        if ($reason !== null) {
            throw ValidationException::withMessages(['attendees' => $reason]);
        }
        if ($event->capacity !== null && $this->reservedPlaces($event) + $places > $event->capacity) {
            throw ValidationException::withMessages(['attendees' => 'There are not enough places for everyone in this registration.']);
        }
    }

    public function ticketIsValid(EventAttendee $attendee): bool
    {
        return $attendee->revoked_at === null && $attendee->registration->confirmed_at !== null
            && $attendee->registration->cancelled_at === null
            && $attendee->registration->event->status === EventStatus::Published;
    }

    /** @param array<string, mixed> $changes */
    public function ensureCanChange(Event $event, array $changes): void
    {
        if ($event->registrations()->exists()) {
            if ($changes['audience'] !== $event->audience->value) {
                throw ValidationException::withMessages(['audience' => 'Audience is locked after the first registration.']);
            }
            if ((int) $changes['price_centavos'] !== $event->price_centavos) {
                throw ValidationException::withMessages(['price_centavos' => 'Price is locked after the first registration.']);
            }
        }
        if ($changes['capacity'] !== null && (int) $changes['capacity'] < $this->reservedPlaces($event)) {
            throw ValidationException::withMessages(['capacity' => 'Capacity cannot be lower than the reserved places.']);
        }
    }
}
