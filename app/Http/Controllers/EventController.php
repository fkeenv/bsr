<?php

namespace App\Http\Controllers;

use App\Actions\Events\ListEvents;
use App\Data\EventData;
use App\Enums\EventPermission;
use App\Models\Event;
use App\Support\EventAccess;
use App\Support\EventRegistrationRules;
use App\Support\PlatformAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventController
{
    public function index(Request $request, ListEvents $events): Response
    {
        return Inertia::render('events/Index', ['events' => $events->handle($request->user())]);
    }

    public function show(Request $request, Event $event, EventAccess $access, EventRegistrationRules $registrations): Response
    {
        $access->ensureCanView($event, $request->user());

        return Inertia::render('events/Show', ['event' => EventData::fromModel($event), 'registrationAvailability' => $registrations->unavailableReason($event, $request->user()), 'submissionKey' => $request->user() !== null ? Str::uuid()->toString() : null]);
    }

    public function image(Request $request, Event $event, EventAccess $access): StreamedResponse
    {
        if (! PlatformAccess::allowsStaff($request->user(), EventPermission::Manage)) {
            $access->ensureCanView($event, $request->user());
        }
        abort_if($event->image_path === null, 404);

        return Storage::disk('local')->response($event->image_path);
    }
}
