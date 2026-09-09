<?php

namespace App\Http\Controllers;

use App\Data\MembershipData;
use App\Models\Membership;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
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

        return Inertia::render('Dashboard', [
            'memberships' => $memberships,
        ]);
    }
}
