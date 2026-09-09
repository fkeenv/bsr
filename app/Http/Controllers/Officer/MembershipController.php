<?php

namespace App\Http\Controllers\Officer;

use App\Actions\Memberships\ChangeMembershipRole;
use App\Actions\Memberships\EndMembership;
use App\Data\MembershipData;
use App\Enums\MembershipRole;
use App\Http\Requests\EndMembershipRequest;
use App\Http\Requests\Officer\ChangeMembershipRoleRequest;
use App\Models\Membership;
use App\Support\FlashToast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MembershipController
{
    public function index(): Response
    {
        $memberships = Membership::query()
            ->live()
            ->with(['user', 'property'])
            ->latest('id')
            ->get()
            ->map(fn (Membership $membership): MembershipData => MembershipData::fromModel($membership))
            ->values()
            ->all();

        return Inertia::render('officer/memberships/Index', [
            'memberships' => $memberships,
        ]);
    }

    public function updateRole(
        ChangeMembershipRoleRequest $request,
        Membership $membership,
        ChangeMembershipRole $changeMembershipRole,
    ): RedirectResponse {
        $role = MembershipRole::from($request->validated('role'));
        $changeMembershipRole->handle($membership, $role);

        FlashToast::success('Membership role updated.');

        return redirect()->route('officer.memberships.index');
    }

    public function end(
        EndMembershipRequest $request,
        Membership $membership,
        EndMembership $endMembership,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor !== null);

        $endMembership->handle(
            $membership,
            $actor,
            $request->validated('end_reason'),
        );

        FlashToast::success('Membership ended.');

        return redirect()->route('officer.memberships.index');
    }
}
