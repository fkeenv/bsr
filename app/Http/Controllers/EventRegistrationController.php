<?php

namespace App\Http\Controllers;

use App\Actions\Events\CancelEventRegistration;
use App\Actions\Events\ListEventRegistrations;
use App\Actions\Events\RegisterForEvent;
use App\Data\EventFinancialData;
use App\Data\EventRegistrationData;
use App\Http\Requests\RegisterForEventRequest;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Support\EventFinancialAccess;
use App\Support\EventRegistrationAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EventRegistrationController
{
    public function store(RegisterForEventRequest $request, Event $event, RegisterForEvent $register): RedirectResponse
    {
        $registration = $register->handle($request->user(), $event, $request->validated());

        return to_route('event-registrations.show', $registration)->with('success', $registration->wasRecentlyCreated
            ? ($registration->price_centavos > 0 ? 'Places reserved for 24 hours. Submit your payment details to continue.' : 'Registration confirmed. Your attendee tickets are ready.') : 'Your existing registration is shown below.');
    }

    public function index(Request $request, ListEventRegistrations $registrations): Response
    {
        return Inertia::render('event-registrations/Index', ['registrations' => $registrations->handle($request->user())]);
    }

    public function show(Request $request, EventRegistration $registration, EventRegistrationAccess $access, EventFinancialAccess $financialAccess): Response
    {
        $access->ensureCanRead($registration, $request->user());

        return Inertia::render('event-registrations/Show', ['registration' => EventRegistrationData::fromModel($registration), 'isOwner' => $registration->user_id === $request->user()->id, 'financial' => $financialAccess->canRead($registration, $request->user()) ? EventFinancialData::fromModel($registration) : null, 'submissionKey' => Str::uuid()->toString()]);
    }

    public function cancel(Request $request, EventRegistration $registration, CancelEventRegistration $cancel): RedirectResponse
    {
        $cancel->handle($request->user(), $registration);

        return back()->with('success', 'Registration cancelled. All places were released and tickets invalidated.');
    }
}
