<?php

namespace App\Enums;

enum EventPermission: string
{
    case Manage = 'events.manage';
    case RegistrationsRead = 'events.registrations.read';
    case PaymentsReview = 'events.payments.review';
    case RefundsReview = 'events.refunds.review';
    case CheckIn = 'events.check-in';

    public function label(): string
    {
        return match ($this) {
            self::Manage => 'Manage Events',
            self::RegistrationsRead => 'View Event registrations',
            self::PaymentsReview => 'Review Event payments',
            self::RefundsReview => 'Review Event refunds',
            self::CheckIn => 'Check in Event attendees',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Manage => 'Create, edit, publish, and cancel association Events.',
            self::RegistrationsRead => 'View registration and attendee records, independently of Event management.',
            self::PaymentsReview => 'Review Event payment records, separately from Property dues.',
            self::RefundsReview => 'Review Event refund records, separately from Property dues.',
            self::CheckIn => 'Validate attendee tickets and record Event attendance.',
        };
    }

    public function feature(): string
    {
        return 'Events';
    }
}
