<?php

namespace App\Http\Controllers\Officer;

use App\Actions\Events\CancelEvent;
use App\Actions\Events\ListEvents;
use App\Actions\Events\PublishEvent;
use App\Actions\Events\RescheduleEvent;
use App\Actions\Events\SaveEvent;
use App\Data\EventData;
use App\Data\EventManagementData;
use App\Data\EventScheduleChangeData;
use App\Http\Requests\Officer\RescheduleEventRequest;
use App\Http\Requests\Officer\SaveEventRequest;
use App\Models\Event;
use App\Support\EventRegistrationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController
{
    public function index(Request $request, ListEvents $events): Response
    {
        return Inertia::render('officer/events/Index', ['events' => $events->handle($request->user(), true)]);
    }

    public function create(): Response
    {
        return Inertia::render('officer/events/Create');
    }

    public function store(SaveEventRequest $request, SaveEvent $save): RedirectResponse
    {
        $event = $save->handle($request->user(), $request->validated());

        return to_route('officer.events.edit', $event)->with('success', 'Event draft saved.');
    }

    public function edit(Event $event, EventRegistrationRules $registrations): Response
    {
        return Inertia::render('officer/events/Edit', ['event' => EventData::fromModel($event),
            'management' => EventManagementData::fromModel($event, $registrations),
            'scheduleChanges' => $event->scheduleChanges()->orderByDesc('id')->get()->map(fn ($change): EventScheduleChangeData => EventScheduleChangeData::fromModel($change))->all()]);
    }

    public function update(SaveEventRequest $request, Event $event, SaveEvent $save): RedirectResponse
    {
        $save->handle($request->user(), $request->validated(), $event);

        return back()->with('success', 'Event updated.');
    }

    public function publish(Request $request, Event $event, PublishEvent $publish): RedirectResponse
    {
        $publish->handle($request->user(), $event);

        return back()->with('success', 'Event published.');
    }

    public function reschedule(RescheduleEventRequest $request, Event $event, RescheduleEvent $reschedule): RedirectResponse
    {
        $reschedule->handle($request->user(), $event, $request->validated());

        return back()->with('success', 'Event rescheduled. Existing registrants have been notified.');
    }

    public function cancel(Request $request, Event $event, CancelEvent $cancel): RedirectResponse
    {
        $cancel->handle($request->user(), $event);

        return back()->with('success', 'Event cancelled. Its history has been retained.');
    }
}
