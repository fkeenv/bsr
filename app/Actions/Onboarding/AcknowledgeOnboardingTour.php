<?php

namespace App\Actions\Onboarding;

use App\Models\OnboardingProgress;

class AcknowledgeOnboardingTour
{
    public function handle(OnboardingProgress $progress): void
    {
        if ($progress->tour_acknowledged_at !== null) {
            return;
        }

        $progress->forceFill(['tour_acknowledged_at' => now()])->save();
    }
}
