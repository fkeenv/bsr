<?php

namespace App\Enums;

enum OnboardingStep: string
{
    case Announcements = 'announcements';
    case StatementOfAccount = 'statement-of-account';
    case PropertyProfile = 'property-profile';

    /**
     * The page whose first linked visit completes this step, or null when another action completes it.
     */
    public function visitRouteName(): ?string
    {
        return match ($this) {
            self::Announcements => 'announcements.index',
            self::StatementOfAccount => 'statement-of-account.index',
            self::PropertyProfile => null,
        };
    }
}
