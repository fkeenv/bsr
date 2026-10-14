<x-mail::message>
# Your registration was cancelled

Your registration for **{{ $registration->event->title }}** was cancelled. All places were released and all tickets in this registration are now invalid.

<x-mail::button :url="route('event-registrations.show', $registration)">
View registration
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
