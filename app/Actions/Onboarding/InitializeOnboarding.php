<?php

namespace App\Actions\Onboarding;

use App\Enums\OnboardingExperience;
use App\Models\OnboardingProgress;
use App\Models\User;

class InitializeOnboarding
{
    public function handle(User $user, OnboardingExperience $experience): OnboardingProgress
    {
        return OnboardingProgress::query()->firstOrCreate([
            'user_id' => $user->id,
            'experience' => $experience,
            'version' => $experience->currentVersion(),
        ], [
            'completed_steps' => [],
        ]);
    }
}
