<?php

namespace App\Http\Controllers;

use App\Actions\Onboarding\FindCurrentOnboardingProgress;
use App\Data\MembershipData;
use App\Data\OnboardingData;
use App\Enums\OnboardingExperience;
use App\Models\Membership;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(FindCurrentOnboardingProgress $findOnboardingProgress): Response
    {
        $user = request()->user();
        assert($user !== null);

        $memberships = Membership::query()
            ->live()
            ->where('user_id', $user->id)
            ->with(['property', 'user'])
            ->latest('id')
            ->get()
            ->map(fn (Membership $membership): MembershipData => MembershipData::fromModel($membership))
            ->values()
            ->all();

        $onboardingProgress = $findOnboardingProgress->handle($user, OnboardingExperience::Member);

        return Inertia::render('Dashboard', [
            'memberships' => $memberships,
            'onboarding' => $onboardingProgress === null ? null : OnboardingData::fromModel($onboardingProgress),
        ]);
    }
}
