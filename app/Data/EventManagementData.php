<?php

namespace App\Data;

use App\Models\Event;
use App\Support\EventRegistrationRules;
use Spatie\LaravelData\Data;

class EventManagementData extends Data
{
    public function __construct(
        public bool $registration_fields_locked,
        public int $reserved_places,
    ) {}

    public static function fromModel(Event $event, EventRegistrationRules $registrations): self
    {
        return new self($registrations->registrationFieldsLocked($event), $registrations->reservedPlaces($event));
    }
}
