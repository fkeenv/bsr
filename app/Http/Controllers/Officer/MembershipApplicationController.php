<?php

namespace App\Http\Controllers\Officer;

use App\Actions\MembershipApplications\ApproveMembershipApplication;
use App\Actions\MembershipApplications\RejectMembershipApplication;
use App\Data\MembershipApplicationData;
use App\Enums\MembershipApplicationStatus;
use App\Enums\MembershipRole;
use App\Http\Requests\Officer\ApproveMembershipApplicationRequest;
use App\Http\Requests\Officer\RejectMembershipApplicationRequest;
use App\Models\MembershipApplication;
use App\Support\FlashToast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MembershipApplicationController
{
    public function index(): Response
    {
        $applications = MembershipApplication::query()
            ->with(['user', 'property', 'householdMembers', 'emergencyContacts', 'vehicles'])
            ->whereIn('status', [
                MembershipApplicationStatus::Pending->value,
                MembershipApplicationStatus::Rejected->value,
            ])
            ->latest('id')
            ->get()
            ->map(fn (MembershipApplication $application): MembershipApplicationData => MembershipApplicationData::fromModel($application))
            ->values()
            ->all();

        return Inertia::render('officer/membership-applications/Index', [
            'applications' => $applications,
        ]);
    }

    public function show(MembershipApplication $membershipApplication): Response
    {
        $membershipApplication->load(['user', 'property', 'householdMembers', 'emergencyContacts', 'vehicles']);

        return Inertia::render('officer/membership-applications/Show', [
            'application' => MembershipApplicationData::fromModel($membershipApplication),
        ]);
    }

    public function approve(
        ApproveMembershipApplicationRequest $request,
        MembershipApplication $membershipApplication,
        ApproveMembershipApplication $approveMembershipApplication,
    ): RedirectResponse {
        $reviewer = $request->user();
        assert($reviewer !== null);

        $role = MembershipRole::from($request->validated('role'));

        $approveMembershipApplication->handle($membershipApplication, $reviewer, $role);

        FlashToast::success('Membership Application approved.');

        return redirect()->route('officer.membership-applications.index');
    }

    public function reject(
        RejectMembershipApplicationRequest $request,
        MembershipApplication $membershipApplication,
        RejectMembershipApplication $rejectMembershipApplication,
    ): RedirectResponse {
        $reviewer = $request->user();
        assert($reviewer !== null);

        $rejectMembershipApplication->handle($membershipApplication, $reviewer);

        FlashToast::success('Membership Application rejected.');

        return redirect()->route('officer.membership-applications.index');
    }
}
