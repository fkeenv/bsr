<?php

namespace App\Actions\Events;

use App\Enums\EventRegistrationStatus;
use App\Mail\EventRegistrationCancelled;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Support\EventRegistrationAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CancelEventRegistration
{
    public function __construct(private EventRegistrationAccess $access, private ExpireEventReservation $expire) {}

    public function handle(User $account, EventRegistration $registration): void
    {
        $this->access->ensureOwner($registration, $account);
        DB::transaction(function () use ($account, $registration): void {
            Event::query()->lockForUpdate()->findOrFail($registration->event_id);
            $registration = EventRegistration::query()->lockForUpdate()->findOrFail($registration->id);
            $this->access->ensureOwner($registration, $account);
            if ($registration->cancelled_at !== null) {
                return;
            }
            if ($registration->status === EventRegistrationStatus::PendingPayment && $registration->expires_at?->lessThanOrEqualTo(now())) {
                $this->expire->handle($registration);

                return;
            }
            if ($registration->price_centavos > 0 && $registration->status !== EventRegistrationStatus::PendingPayment) {
                throw ValidationException::withMessages(['registration' => 'Submit a cancellation request for a paid registration with submitted or confirmed payment.']);
            }
            $registration->update(['cancelled_at' => now(), 'status' => EventRegistrationStatus::Cancelled]);
            $registration->attendees()->whereNull('revoked_at')->update(['revoked_at' => now()]);
            Mail::to($account)->queue((new EventRegistrationCancelled($registration))->afterCommit());
        }, 5);
    }
}
