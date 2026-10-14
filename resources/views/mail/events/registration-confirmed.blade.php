<x-mail::message>
# Your registration is confirmed

Your registration for **{{ $registration->event->title }}** was confirmed for {{ $registration->attendee_count }} attendees.

@foreach ($registration->attendees as $attendee)
- {{ $attendee->name }}
@endforeach

Open your registration to view the current status and each attendee's private QR ticket.

<x-mail::button :url="route('event-registrations.show', $registration)">
View registration and tickets
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
