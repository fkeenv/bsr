<?php

namespace App\Actions\Onboarding;

use App\Enums\OnboardingExperience;
use App\Models\OnboardingProgress;
use App\Models\User;

class FindCurrentOnboardingProgress
{
    public function handle(User $user, OnboardingExperience $experience): ?OnboardingProgress
    {
        if (! $experience->isAvailableTo($user)) {
            return null;
        }

        return OnboardingProgress::query()
            ->whereBelongsTo($user)
            ->where('experience', $experience)
            ->where('version', $experience->currentVersion())
            ->first();
    }
}
