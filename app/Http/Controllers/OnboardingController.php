<?php

namespace App\Http\Controllers;

use App\Actions\Onboarding\AcknowledgeOnboardingTour;
use App\Actions\Onboarding\CompleteOnboardingStep;
use App\Actions\Onboarding\FindCurrentOnboardingProgress;
use App\Enums\OnboardingExperience;
use App\Http\Requests\RecordOnboardingStepRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OnboardingController extends Controller
{
    public function acknowledgeTour(
        Request $request,
        OnboardingExperience $experience,
        FindCurrentOnboardingProgress $findProgress,
        AcknowledgeOnboardingTour $acknowledgeTour,
    ): Response {
        $user = $request->user();
        assert($user !== null);

        $acknowledgeTour->handle($findProgress->handleOrFail($user, $experience));

        return response()->noContent();
    }

    public function completeStep(
        RecordOnboardingStepRequest $request,
        OnboardingExperience $experience,
        FindCurrentOnboardingProgress $findProgress,
        CompleteOnboardingStep $completeStep,
    ): RedirectResponse {
        $user = $request->user();
        assert($user !== null);

        $step = $request->step();
        $completeStep->handle($findProgress->handleOrFail($user, $experience), $step);

        $routeName = $step->visitRouteName();
        assert($routeName !== null);

        return redirect()->route($routeName);
    }
}
