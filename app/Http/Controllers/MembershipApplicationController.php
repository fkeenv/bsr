<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class MembershipApplicationController extends Controller
{
    /**
     * Show the Membership Application onboarding stub.
     */
    public function create(): Response
    {
        return Inertia::render('membership-application/Create');
    }
}
