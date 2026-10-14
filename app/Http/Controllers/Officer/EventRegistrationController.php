<?php

namespace App\Http\Controllers\Officer;

use App\Actions\Events\ListEventRegistrations;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventRegistrationController
{
    public function index(Request $request, ListEventRegistrations $registrations): Response
    {
        return Inertia::render('officer/event-registrations/Index', ['registrations' => $registrations->handle($request->user(), true)]);
    }
}
