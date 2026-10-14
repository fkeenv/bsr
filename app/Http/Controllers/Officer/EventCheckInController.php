<?php

namespace App\Http\Controllers\Officer;

use App\Actions\Events\CheckInEventAttendee;
use App\Actions\Events\FindEventAttendees;
use App\Actions\Events\ListCheckInEvents;
use App\Data\EventData;
use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EventCheckInController
{
    public function index(Request $request, ListCheckInEvents $events): Response
    {
        return Inertia::render('officer/event-check-in/Index', ['events' => $events->handle($request->user())])->toResponse($request)->setPrivate();
    }

    public function show(Request $request, Event $event, FindEventAttendees $find): Response
    {
        abort_unless($event->status === EventStatus::Published, 404);
        $data = $request->validate(['search' => ['nullable', 'string', 'max:255'], 'page' => ['nullable', 'integer', 'min:1']]);
        $response = Inertia::render('officer/event-check-in/Show', ['event' => EventData::fromModel($event, false),
            'attendees' => $find->handle($request->user(), $event, $data['search'] ?? '', (int) ($data['page'] ?? 1)), 'search' => $data['search'] ?? ''])->toResponse($request);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    public function store(Request $request, Event $event, CheckInEventAttendee $checkIn): JsonResponse
    {
        $data = $request->validate(['ticket' => ['required', 'string', 'max:2048'], 'checked_in_at' => ['prohibited'], 'checked_in_by_user_id' => ['prohibited'], 'attendee_id' => ['prohibited']]);

        return response()->json($checkIn->handle($request->user(), $event, $data['ticket'])->toArray())->header('Cache-Control', 'private, no-store');
    }
}
