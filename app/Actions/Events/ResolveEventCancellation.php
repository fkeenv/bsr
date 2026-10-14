<?php

namespace App\Actions\Events;

use App\Enums\EventPermission;
use App\Enums\EventRegistrationStatus;
use App\Mail\EventRegistrationUpdated;
use App\Models\Event;
use App\Models\EventCancellationRequest;
use App\Models\EventRegistration;
use App\Models\User;
use App\Support\PlatformAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ResolveEventCancellation
{
    /** @param array<string, mixed> $data */
    public function handle(User $actor, EventCancellationRequest $cancellation, array $data): void
    {
        abort_unless(PlatformAccess::allowsStaff($actor, EventPermission::RefundsReview), 403);
        $data = Validator::make($data, ['decision' => ['required', 'in:approved,declined'], 'reason' => ['required', 'string', 'max:10000']])->validate();
        DB::transaction(function () use ($actor, $cancellation, $data): void {
            Event::query()->lockForUpdate()->findOrFail($cancellation->registration->event_id);
            $registration = EventRegistration::query()->lockForUpdate()->findOrFail($cancellation->event_registration_id);
            $cancellation = EventCancellationRequest::query()->lockForUpdate()->findOrFail($cancellation->id);
            if ($cancellation->status === $data['decision']) {
                return;
            }
            if ($cancellation->status !== 'pending') {
                throw ValidationException::withMessages(['cancellation' => 'This cancellation request has already been resolved.']);
            }
            $cancellation->update(['status' => $data['decision'], 'resolved_by_user_id' => $actor->id, 'resolved_at' => now(), 'resolution_reason' => trim($data['reason'])]);
            if ($data['decision'] === 'approved' && $registration->cancelled_at === null) {
                $registration->update(['status' => EventRegistrationStatus::Cancelled, 'cancelled_at' => now()]);
                $registration->attendees()->whereNull('revoked_at')->update(['revoked_at' => now()]);
            }
            $message = $data['decision'] === 'approved' ? 'All places were released and tickets invalidated. Approval does not mean money has been returned. Refunds are recorded separately.' : 'Your registration remains unchanged.';
            Mail::to($registration->user)->queue((new EventRegistrationUpdated($registration, 'Your Event cancellation was '.$data['decision'], $message.' Reason: '.$data['reason']))->afterCommit());
        }, 5);
    }
}
