<?php

namespace App\Enums;

enum OnboardingStep: string
{
    case Announcements = 'announcements';
    case StatementOfAccount = 'statement-of-account';
    case PropertyProfile = 'property-profile';
    case OfficerInvitations = 'officer-invitations';
    case OfficerProperties = 'officer-properties';
    case OfficerMemberships = 'officer-memberships';
    case OfficerPayments = 'officer-payments';
    case OfficerCharges = 'officer-charges';
    case OfficerAnnouncements = 'officer-announcements';

    /**
     * The page whose first linked visit completes this step, or null when another action completes it.
     */
    public function visitRouteName(): ?string
    {
        return match ($this) {
            self::Announcements => 'announcements.index',
            self::StatementOfAccount => 'statement-of-account.index',
            self::PropertyProfile => null,
            self::OfficerInvitations => 'officer.property-invitations.index',
            self::OfficerProperties => 'officer.properties.index',
            self::OfficerMemberships => 'officer.memberships.index',
            self::OfficerPayments => 'officer.payments.index',
            self::OfficerCharges => 'officer.charges.index',
            self::OfficerAnnouncements => 'officer.announcements.index',
        };
    }
}
