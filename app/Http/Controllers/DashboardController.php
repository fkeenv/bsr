<?php

namespace App\Http\Controllers;

use App\Actions\Onboarding\FindCurrentOnboardingProgress;
use App\Data\MembershipData;
use App\Data\OnboardingData;
use App\Enums\MembershipRole;
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

        $liveMemberships = Membership::query()
            ->live()
            ->where('user_id', $user->id)
            ->with(['property', 'user'])
            ->latest('id')
            ->get();
        $memberships = $liveMemberships
            ->map(fn (Membership $membership): MembershipData => MembershipData::fromModel($membership))
            ->values()
            ->all();

        $onboardingProgress = $findOnboardingProgress->handle($user, OnboardingExperience::Member);
        $ownerMemberships = $liveMemberships->filter(fn (Membership $membership): bool => $membership->role === MembershipRole::Owner);

        return Inertia::render('Dashboard', [
            'memberships' => $memberships,
            'ownerInvitationPropertyIds' => $ownerMemberships->pluck('property_id')->values()->all(),
            'activeOwnerInvitationPropertyIds' => $ownerMemberships
                ->filter(fn (Membership $membership): bool => $membership->property->is_active)
                ->pluck('property_id')->values()->all(),
            'onboarding' => $onboardingProgress === null ? null : OnboardingData::fromModel($onboardingProgress),
        ]);
    }
}
