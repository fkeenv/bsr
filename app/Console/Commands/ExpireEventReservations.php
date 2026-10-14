<?php

namespace App\Console\Commands;

use App\Actions\Events\ExpireEventReservation;
use App\Enums\EventRegistrationStatus;
use App\Models\EventRegistration;
use Illuminate\Console\Command;

class ExpireEventReservations extends Command
{
    protected $signature = 'events:expire-reservations';

    protected $description = 'Release expired unpaid Event places and notify their account owners';

    public function handle(ExpireEventReservation $expire): int
    {
        $expired = 0;
        EventRegistration::query()->where('status', EventRegistrationStatus::PendingPayment)->where('expires_at', '<=', now())
            ->eachById(function (EventRegistration $registration) use ($expire, &$expired): void {
                if ($expire->handle($registration)) {
                    $expired++;
                }
            }, 100);
        $this->info("Expired {$expired} reservations.");

        return self::SUCCESS;
    }
}
