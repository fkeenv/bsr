<?php

namespace App\Actions\Events;

use App\Enums\EventRegistrationStatus;
use App\Mail\EventRegistrationConfirmed;
use App\Mail\EventRegistrationUpdated;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Support\EventRegistrationRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisterForEvent
{
    public function __construct(private EventRegistrationRules $rules) {}

    /** @param array<string, mixed> $data */
    public function handle(User $account, Event $event, array $data): EventRegistration
    {
        $data = Validator::make($data, EventRegistrationRules::submissionRules())->validate();
        $names = array_map(fn (array $attendee): string => trim($attendee['name']), $data['attendees']);
        $hash = hash('sha256', json_encode($names, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($account, $event, $data, $names, $hash): EventRegistration {
            $event = Event::query()->lockForUpdate()->findOrFail($event->id);
            $previous = $event->registrations()->where('user_id', $account->id)
                ->where('submission_key', $data['submission_key'])->first();
            if ($previous !== null) {
                if ($previous->submission_hash !== $hash) {
                    throw ValidationException::withMessages(['attendees' => 'This submission was already used. Start a new registration for different attendees.']);
                }

                return $previous;
            }
            $this->rules->ensureCanReserve($event, $account, count($names));
            $registration = $event->registrations()->create([
                'user_id' => $account->id, 'attendee_count' => count($names),
                'submission_key' => $data['submission_key'], 'submission_hash' => $hash,
                'status' => $event->price_centavos > 0 ? EventRegistrationStatus::PendingPayment : EventRegistrationStatus::Confirmed,
                'price_centavos' => $event->price_centavos, 'total_centavos' => $event->price_centavos * count($names),
                'payment_instructions' => $event->payment_instructions,
                'expires_at' => $event->price_centavos > 0 ? now()->addHours(24) : null,
                'confirmed_at' => $event->price_centavos === 0 ? now() : null,
            ]);
            foreach ($names as $name) {
                $registration->attendees()->create(['name' => $name, 'ticket_token' => $event->price_centavos === 0 ? Str::random(64) : null]);
            }
            $mail = $event->price_centavos === 0 ? new EventRegistrationConfirmed($registration)
                : new EventRegistrationUpdated($registration, 'Your Event places are reserved', 'Submit your external payment details within 24 hours. Tickets are issued after staff confirm payment.');
            Mail::to($account)->queue($mail->afterCommit());

            return $registration;
        }, 5);
    }
}
