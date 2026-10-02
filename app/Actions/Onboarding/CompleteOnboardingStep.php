<?php

namespace App\Actions\Onboarding;

use App\Enums\OnboardingStep;
use App\Models\OnboardingProgress;
use InvalidArgumentException;

class CompleteOnboardingStep
{
    public function handle(OnboardingProgress $progress, OnboardingStep $step): void
    {
        if (! in_array($step, $progress->experience->steps(), true)) {
            throw new InvalidArgumentException("Step [{$step->value}] is not part of the [{$progress->experience->value}] onboarding.");
        }

        if ($progress->hasCompleted($step)) {
            return;
        }

        $progress->forceFill([
            'completed_steps' => [...$progress->completed_steps, $step->value],
        ])->save();
    }
}
