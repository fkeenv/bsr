<?php

namespace App\Actions\Events;

use App\Enums\EventRegistrationStatus;
use App\Mail\EventRegistrationUpdated;
use App\Models\Event;
use App\Models\EventCancellationRequest;
use App\Models\EventRegistration;
use App\Models\User;
use App\Support\EventRegistrationAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RequestEventCancellation
{
    public function __construct(private EventRegistrationAccess $access) {}

    /** @param array<string, mixed> $data */
    public function handle(User $account, EventRegistration $registration, array $data): EventCancellationRequest
    {
        $this->access->ensureOwner($registration, $account);
        $data = Validator::make($data, ['submission_key' => ['required', 'uuid'], 'reason' => ['required', 'string', 'max:10000']])->validate();

        return DB::transaction(function () use ($account, $registration, $data): EventCancellationRequest {
            Event::query()->lockForUpdate()->findOrFail($registration->event_id);
            $registration = EventRegistration::query()->lockForUpdate()->findOrFail($registration->id);
            $this->access->ensureOwner($registration, $account);
            $previous = $registration->cancellationRequests()->where('submission_key', $data['submission_key'])->first();
            if ($previous !== null) {
                if ($previous->reason !== trim($data['reason'])) {
                    throw ValidationException::withMessages(['reason' => 'This request key was already used.']);
                }

                return $previous;
            }
            if ($registration->price_centavos <= 0 || ! in_array($registration->status, [EventRegistrationStatus::AwaitingReview, EventRegistrationStatus::Confirmed], true)) {
                throw ValidationException::withMessages(['registration' => 'Only submitted or confirmed paid registrations need a cancellation request.']);
            }
            if ($registration->cancellationRequests()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['registration' => 'Your cancellation request is already awaiting review.']);
            }
            $request = $registration->cancellationRequests()->create(['submission_key' => $data['submission_key'], 'reason' => trim($data['reason'])]);
            Mail::to($account)->queue((new EventRegistrationUpdated($registration, 'Your Event cancellation request was received', 'All places remain reserved until staff approve the whole-registration cancellation.'))->afterCommit());

            return $request;
        }, 5);
    }
}
