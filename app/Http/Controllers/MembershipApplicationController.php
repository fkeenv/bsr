<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class MembershipApplicationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('membership-application/Create');
    }
}
