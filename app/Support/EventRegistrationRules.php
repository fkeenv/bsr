<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Validation\ValidationException;

class EventRegistrationRules
{
    public function reservedPlaces(Event $event): int
    {
        return (int) $event->registrations()->whereNull('cancelled_at')->sum('attendee_count');
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
