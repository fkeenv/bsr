<?php

namespace App\Actions\Events;

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
    public function __construct(private EventRegistrationAccess $access) {}

    public function handle(User $account, EventRegistration $registration): void
    {
        $this->access->ensureOwner($registration, $account);
        DB::transaction(function () use ($account, $registration): void {
            $event = Event::query()->lockForUpdate()->findOrFail($registration->event_id);
            $registration = EventRegistration::query()->lockForUpdate()->findOrFail($registration->id);
            $this->access->ensureOwner($registration, $account);
            if ($registration->cancelled_at !== null) {
                return;
            }
            if ($event->price_centavos !== 0) {
                throw ValidationException::withMessages(['registration' => 'Paid registration cancellation is not available here.']);
            }
            $registration->update(['cancelled_at' => now()]);
            $registration->attendees()->whereNull('revoked_at')->update(['revoked_at' => now()]);
            Mail::to($account)->queue((new EventRegistrationCancelled($registration))->afterCommit());
        }, 5);
    }
}
