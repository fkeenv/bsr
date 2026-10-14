<?php

namespace App\Http\Controllers;

use App\Data\EventData;
use App\Data\EventTicketData;
use App\Models\EventAttendee;
use App\Support\EventRegistrationAccess;
use App\Support\EventRegistrationRules;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class EventTicketController
{
    public function show(Request $request, EventAttendee $attendee, EventRegistrationAccess $access, EventRegistrationRules $rules): Response
    {
        $access->ensureCanRead($attendee->registration, $request->user());

        return Inertia::render('event-tickets/Show', ['ticket' => EventTicketData::fromModel($attendee, $rules),
            'event' => EventData::fromModel($attendee->registration->event, false), 'registrationId' => $attendee->event_registration_id]);
    }

    public function qr(Request $request, EventAttendee $attendee, EventRegistrationAccess $access, EventRegistrationRules $rules): HttpResponse
    {
        $access->ensureCanRead($attendee->registration, $request->user());
        abort_unless($rules->ticketIsValid($attendee), 410);
        $writer = new Writer(new ImageRenderer(new RendererStyle(256), new SvgImageBackEnd));

        return response($writer->writeString(route('event-tickets.show', ['attendee' => $attendee->ticket_token])), 200,
            ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'private, no-store']);
    }
}
