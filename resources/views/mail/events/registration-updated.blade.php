<x-mail::message>
# {{ $headline }}

{{ $registration->event->title }} · {{ $registration->attendee_count }} attendees

{{ $message }}

<x-mail::button :url="route('event-registrations.show', $registration)">View registration</x-mail::button>
</x-mail::message>
