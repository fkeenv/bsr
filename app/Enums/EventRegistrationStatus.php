<?php

namespace App\Enums;

enum EventRegistrationStatus: string
{
    case PendingPayment = 'pending-payment';
    case AwaitingReview = 'awaiting-review';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Awaiting payment', self::AwaitingReview => 'Awaiting review',
            self::Confirmed => 'Confirmed', self::Cancelled => 'Cancelled', self::Expired => 'Expired', self::Rejected => 'Rejected',
        };
    }
}
