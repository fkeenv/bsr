<?php

namespace App\Http\Controllers;

use App\Actions\Onboarding\AcknowledgeOnboardingTour;
use App\Actions\Onboarding\CompleteOnboardingStep;
use App\Actions\Onboarding\FindCurrentOnboardingProgress;
use App\Enums\OnboardingExperience;
use App\Http\Requests\RecordOnboardingStepRequest;
use App\Models\OnboardingProgress;
use App\Models\User;
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

        $acknowledgeTour->handle($this->currentProgressOrFail($findProgress, $user, $experience));

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
        $completeStep->handle($this->currentProgressOrFail($findProgress, $user, $experience), $step);

        $routeName = $step->visitRouteName();
        assert($routeName !== null);

        return redirect()->route($routeName);
    }

    private function currentProgressOrFail(
        FindCurrentOnboardingProgress $findProgress,
        User $user,
        OnboardingExperience $experience,
    ): OnboardingProgress {
        abort_unless($experience->isAvailableTo($user), 403);

        $progress = $findProgress->handle($user, $experience);
        abort_if($progress === null, 404);

        return $progress;
    }
}
