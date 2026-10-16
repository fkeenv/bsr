<?php

namespace App\Actions\Events;

use App\Enums\EventPermission;
use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Mail\EventRegistrationUpdated;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Support\PlatformAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CancelEvent
{
    public function handle(User $actor, Event $event): void
    {
        abort_unless(PlatformAccess::allowsStaff($actor, EventPermission::Manage), 403);
        DB::transaction(function () use ($actor, $event): void {
            $event = Event::query()->lockForUpdate()->findOrFail($event->id);
            if ($event->status === EventStatus::Cancelled) {
                return;
            }
            $event->update(['status' => EventStatus::Cancelled, 'cancelled_at' => now(), 'updated_by_user_id' => $actor->id]);
            $event->registrations()->with('user')->eachById(function (EventRegistration $registration) use ($actor): void {
                if (in_array($registration->status, [EventRegistrationStatus::PendingPayment, EventRegistrationStatus::AwaitingReview, EventRegistrationStatus::Confirmed], true)) {
                    $expired = $registration->status === EventRegistrationStatus::PendingPayment && $registration->expires_at?->lessThanOrEqualTo(now());
                    $registration->update(['status' => $expired ? EventRegistrationStatus::Expired : EventRegistrationStatus::Cancelled,
                        'cancelled_at' => $registration->cancelled_at ?? now(), 'expired_at' => $expired ? now() : $registration->expired_at]);
                }
                $registration->attendees()->whereNull('revoked_at')->update(['revoked_at' => now()]);
                $registration->cancellationRequests()->where('status', 'pending')->update(['status' => 'approved',
                    'resolved_by_user_id' => $actor->id, 'resolved_at' => now(), 'resolution_reason' => 'The entire Event was cancelled. Refunds are recorded separately.']);
                Mail::to($registration->user)->queue((new EventRegistrationUpdated($registration, 'Your Event was cancelled',
                    'The entire Event was cancelled. All tickets are invalid and reservations are closed. Confirmed payments not yet fully refunded require full refund resolution. Submitted payments will still be reviewed. No money has been returned by this cancellation; staff record external refunds separately.'))->afterCommit());
            }, 100);
        }, 5);
    }
}
