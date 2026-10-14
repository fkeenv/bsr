<?php

use App\Mail\EventRegistrationCancelled;
use App\Mail\EventRegistrationConfirmed;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\EventRegistration;

test('registration mail renders escaped attendee content and a private details link', function () {
    $event = Event::factory()->published()->create(['title' => 'Community <script>alert(1)</script>']);
    $registration = EventRegistration::factory()->create(['event_id' => $event->id]);
    EventAttendee::factory()->create(['event_registration_id' => $registration->id, 'name' => '<script>alert("guest")</script>']);
    $confirmation = new EventRegistrationConfirmed($registration);
    $confirmation->assertSeeInHtml(route('event-registrations.show', $registration));
    $confirmation->assertSeeInText('Your registration is confirmed');
    $html = $confirmation->render();
    expect($html)->not->toContain('<script>alert(1)</script>')->not->toContain('<script>alert("guest")</script>');
    expect($html)->toContain('&lt;script&gt;');
    $cancelled = new EventRegistrationCancelled($registration);
    $cancelled->assertSeeInText('All places were released');
    $cancelled->assertSeeInHtml(route('event-registrations.show', $registration));
});
