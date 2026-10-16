<?php

namespace App\Actions\Events;

use App\Enums\EventPermission;
use App\Enums\EventRegistrationStatus;
use App\Mail\EventRegistrationConfirmed;
use App\Mail\EventRegistrationUpdated;
use App\Models\Event;
use App\Models\EventPaymentRecord;
use App\Models\EventRegistration;
use App\Models\User;
use App\Support\PlatformAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReviewEventPayment
{
    /** @param array<string, mixed> $data */
    public function handle(User $actor, EventPaymentRecord $payment, array $data): void
    {
        abort_unless(PlatformAccess::allowsStaff($actor, EventPermission::PaymentsReview), 403);
        $data = Validator::make($data, ['decision' => ['required', 'in:confirmed,rejected'], 'reason' => ['nullable', 'string', 'max:10000', 'required_if:decision,rejected']])->validate();
        DB::transaction(function () use ($actor, $payment, $data): void {
            $event = Event::query()->lockForUpdate()->findOrFail($payment->registration->event_id);
            $registration = EventRegistration::query()->lockForUpdate()->findOrFail($payment->event_registration_id);
            $payment = EventPaymentRecord::query()->lockForUpdate()->findOrFail($payment->id);
            if ($payment->status === $data['decision']) {
                return;
            }
            if ($payment->status !== 'submitted' || ! in_array($registration->status, [EventRegistrationStatus::AwaitingReview, EventRegistrationStatus::Cancelled], true)) {
                throw ValidationException::withMessages(['payment' => 'This payment has already been resolved.']);
            }
            if ($data['decision'] === 'confirmed' && $registration->status !== EventRegistrationStatus::Cancelled && ! $event->status->isPublished()) {
                throw ValidationException::withMessages(['payment' => 'A cancelled Event cannot issue tickets.']);
            }
            $payment->update(['status' => $data['decision'], 'reviewed_by_user_id' => $actor->id, 'reviewed_at' => now(), 'review_reason' => $data['reason'] ?? null]);
            if ($registration->status === EventRegistrationStatus::Cancelled) {
                Mail::to($registration->user)->queue((new EventRegistrationUpdated($registration, 'Your Event payment was '.$data['decision'], 'Your registration remains cancelled and no tickets were issued. Refunds are recorded separately.'.(isset($data['reason']) ? ' Reason: '.$data['reason'] : '')))->afterCommit());
            } elseif ($data['decision'] === 'confirmed') {
                $registration->update(['status' => EventRegistrationStatus::Confirmed, 'confirmed_at' => now()]);
                foreach ($registration->attendees as $attendee) {
                    $attendee->update(['ticket_token' => Str::random(64)]);
                }
                Mail::to($registration->user)->queue((new EventRegistrationConfirmed($registration))->afterCommit());
            } else {
                $registration->update(['status' => EventRegistrationStatus::Rejected, 'cancelled_at' => now()]);
                $registration->attendees()->update(['revoked_at' => now()]);
                Mail::to($registration->user)->queue((new EventRegistrationUpdated($registration, 'Your Event payment was rejected', 'Your places were released. Reason: '.$data['reason']))->afterCommit());
            }
        }, 5);
    }
}
