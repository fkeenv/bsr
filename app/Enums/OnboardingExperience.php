<?php

namespace App\Enums;

use App\Models\User;

enum OnboardingExperience: string
{
    case Member = 'member';
    case Officer = 'officer';

    public function currentVersion(): int
    {
        return match ($this) {
            self::Member => 1,
            self::Officer => 1,
        };
    }

    /**
     * @return list<OnboardingStep>
     */
    public function steps(): array
    {
        return match ($this) {
            self::Member => [
                OnboardingStep::Announcements,
                OnboardingStep::StatementOfAccount,
                OnboardingStep::PropertyProfile,
            ],
            self::Officer => [
                OnboardingStep::OfficerInvitations,
                OnboardingStep::OfficerProperties,
                OnboardingStep::OfficerMemberships,
                OnboardingStep::OfficerPayments,
                OnboardingStep::OfficerCharges,
                OnboardingStep::OfficerAnnouncements,
            ],
        };
    }

    /**
     * @return list<OnboardingStep>
     */
    public function stepsCompletedByVisit(): array
    {
        return array_values(array_filter(
            $this->steps(),
            fn (OnboardingStep $step): bool => $step->visitRouteName() !== null,
        ));
    }

    public function isAvailableTo(User $user): bool
    {
        return match ($this) {
            self::Member => $user->hasLiveMembership(),
            self::Officer => $user->canAccessOfficerSurfaces(),
        };
    }
}
