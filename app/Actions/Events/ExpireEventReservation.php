<?php

namespace App\Actions\Events;

use App\Enums\EventRegistrationStatus;
use App\Mail\EventRegistrationUpdated;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ExpireEventReservation
{
    public function handle(EventRegistration $registration): bool
    {
        return DB::transaction(function () use ($registration): bool {
            Event::query()->lockForUpdate()->findOrFail($registration->event_id);
            $registration = EventRegistration::query()->lockForUpdate()->findOrFail($registration->id);
            if ($registration->status !== EventRegistrationStatus::PendingPayment || $registration->expires_at === null || $registration->expires_at->isFuture()) {
                return false;
            }
            $registration->update(['status' => EventRegistrationStatus::Expired, 'cancelled_at' => now(), 'expired_at' => now()]);
            Mail::to($registration->user)->queue((new EventRegistrationUpdated($registration, 'Your Event reservation expired', 'Payment was not submitted within 24 hours. Your places have been released. You may register again if places remain.'))->afterCommit());

            return true;
        }, 5);
    }
}
