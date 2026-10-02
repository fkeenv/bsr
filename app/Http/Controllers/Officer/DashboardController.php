<?php

namespace App\Http\Controllers\Officer;

use App\Actions\Onboarding\FindCurrentOnboardingProgress;
use App\Data\OnboardingData;
use App\Enums\OnboardingExperience;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, FindCurrentOnboardingProgress $findOnboardingProgress): Response
    {
        $user = $request->user();
        assert($user !== null);

        $onboardingProgress = $findOnboardingProgress->handle($user, OnboardingExperience::Officer);

        return Inertia::render('officer/Dashboard', [
            'onboarding' => $onboardingProgress === null ? null : OnboardingData::fromModel($onboardingProgress),
        ]);
    }
}
