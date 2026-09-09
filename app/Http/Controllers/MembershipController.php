<?php

namespace App\Http\Controllers;

use App\Actions\Memberships\EndMembership;
use App\Http\Requests\EndMembershipRequest;
use App\Models\Membership;
use App\Support\FlashToast;
use Illuminate\Http\RedirectResponse;

class MembershipController extends Controller
{
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

        if ($actor->canAccessOfficerSurfaces() && $actor->id !== $membership->user_id) {
            return redirect()->route('officer.memberships.index');
        }

        return redirect()->route('dashboard');
    }
}
