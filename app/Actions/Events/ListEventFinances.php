<?php

namespace App\Actions\Events;

use App\Data\EventFinancialData;
use App\Data\EventRegistrationData;
use App\Enums\EventPermission;
use App\Models\EventRegistration;
use App\Models\User;
use App\Support\PlatformAccess;
use Illuminate\Support\Str;

class ListEventFinances
{
    /** @return list<array{registration: EventRegistrationData, financial: EventFinancialData, submission_key: string}> */
    public function handle(User $actor): array
    {
        abort_unless(PlatformAccess::allowsStaff($actor, EventPermission::PaymentsReview)
            || PlatformAccess::allowsStaff($actor, EventPermission::RefundsReview), 403);

        return array_values(EventRegistration::query()->where('price_centavos', '>', 0)
            ->with(['event', 'user', 'payment.refunds', 'cancellationRequests'])->latest('id')->get()
            ->map(fn (EventRegistration $registration): array => [
                'registration' => EventRegistrationData::fromModel($registration, false),
                'financial' => EventFinancialData::fromModel($registration),
                'submission_key' => Str::uuid()->toString(),
            ])->all());
    }
}
