<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\User;
use App\Support\EventRegistrationAccess;
use App\Support\EventRegistrationRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CorrectEventAttendeeName
{
    public function __construct(private EventRegistrationAccess $access, private EventRegistrationRules $rules) {}

    public function handle(User $account, EventAttendee $attendee, string $name): void
    {
        Validator::make(['name' => $name], ['name' => EventRegistrationRules::nameRules()])->validate();
        $this->access->ensureOwner($attendee->registration, $account);
        DB::transaction(function () use ($account, $attendee, $name): void {
            Event::query()->lockForUpdate()->findOrFail($attendee->registration->event_id);
            $attendee = EventAttendee::query()->lockForUpdate()->findOrFail($attendee->id);
            $this->access->ensureOwner($attendee->registration, $account);
            if ($attendee->checked_in_at !== null || ! $this->rules->ticketIsValid($attendee)) {
                throw ValidationException::withMessages(['name' => 'Names can only be corrected on active tickets before check-in.']);
            }
            $attendee->update(['name' => trim($name)]);
        }, 5);
    }
}
