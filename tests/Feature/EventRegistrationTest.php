<?php

use App\Enums\EventAudience;
use App\Enums\EventStatus;
use App\Mail\EventRegistrationCancelled;
use App\Mail\EventRegistrationConfirmed;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\EventRegistration;
use App\Models\User;
use App\Support\EventRegistrationRules;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(Carbon::parse('2026-10-14 00:00:00', 'UTC'));
});

test('an account without Memberships registers a named group and immediately receives individual tickets', function () {
    Mail::fake();
    $event = Event::factory()->published()->create(['capacity' => 3]);
    $user = User::factory()->create();
    $this->actingAs($user)->post('/events/'.$event->id.'/registrations', [
        'submission_key' => Str::uuid()->toString(),
        'attendees' => [['name' => 'Maria Santos'], ['name' => 'Jose Santos']],
    ])->assertSessionHasNoErrors()->assertRedirect();
    $registration = EventRegistration::query()->sole();
    expect($registration->user_id)->toBe($user->id);
    expect($registration->attendee_count)->toBe(2);
    expect($registration->confirmed_at)->not->toBeNull();
    expect(EventAttendee::count())->toBe(2);
});

function eventRegistrationPayload(array $names = ['Maria Santos'], ?string $key = null): array
{
    return ['submission_key' => $key ?? Str::uuid()->toString(), 'attendees' => array_map(fn ($name) => ['name' => $name], $names)];
}

test('confirmation mail and opaque ticket identities are created once while new submissions allow more attendees', function () {
    Mail::fake();
    $event = Event::factory()->published()->create(['capacity' => 3]);
    $user = User::factory()->create();
    $this->actingAs($user);
    $payload = eventRegistrationPayload(['Maria Santos', 'Jose Santos']);
    $this->post(route('events.registrations.store', $event), $payload)->assertSessionHasNoErrors();
    $registration = EventRegistration::query()->sole();
    $this->post(route('events.registrations.store', $event), $payload)->assertRedirect(route('event-registrations.show', $registration));
    expect(EventRegistration::count())->toBe(1);
    expect(EventAttendee::pluck('ticket_token')->unique()->count())->toBe(2);
    expect(EventAttendee::query()->firstOrFail()->ticket_token)->toHaveLength(64);
    Mail::assertQueued(EventRegistrationConfirmed::class, fn ($mail) => $mail->hasTo($user->email) && $mail->afterCommit === true);
    Mail::assertQueuedCount(1);
    $this->post(route('events.registrations.store', $event), eventRegistrationPayload(['Ana Santos']))->assertSessionHasNoErrors();
    expect(EventRegistration::count())->toBe(2);
    expect(EventAttendee::count())->toBe(3);
    Mail::assertQueuedCount(2);
});

test('a reused submission key cannot change attendee names or revive a cancelled registration', function () {
    Mail::fake();
    $event = Event::factory()->published()->create();
    $this->actingAs(User::factory()->create());
    $payload = eventRegistrationPayload();
    $this->post(route('events.registrations.store', $event), $payload);
    $registration = EventRegistration::query()->sole();
    $this->post(route('events.registrations.store', $event), eventRegistrationPayload(['Someone else'], $payload['submission_key']))->assertSessionHasErrors('attendees');
    $this->post(route('event-registrations.cancel', $registration))->assertSessionHasNoErrors();
    $this->post(route('events.registrations.store', $event), $payload)->assertRedirect(route('event-registrations.show', $registration));
    expect(EventRegistration::count())->toBe(1);
    expect($registration->fresh()->cancelled_at)->not->toBeNull();
    Mail::assertQueued(EventRegistrationConfirmed::class, 1);
});

test('guests cannot register and Members only Events require a live Membership for each new registration', function () {
    Mail::fake();
    $event = Event::factory()->published()->create(['audience' => EventAudience::MembersOnly]);
    $this->post(route('events.registrations.store', $event), eventRegistrationPayload())->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->post(route('events.registrations.store', $event), eventRegistrationPayload())->assertSessionHasErrors('attendees');
    $member = User::factory()->member()->create();
    $this->actingAs($member)->post(route('events.registrations.store', $event), eventRegistrationPayload(['Young guest', 'Older guest']))->assertSessionHasNoErrors();
    $registration = EventRegistration::query()->sole();
    $ticket = $registration->attendees()->firstOrFail();
    $member->memberships()->update(['ended_at' => now()]);
    $this->get(route('event-registrations.show', $registration))->assertOk();
    $this->get(route('event-tickets.qr', ['attendee' => $ticket->ticket_token]))->assertOk();
    $this->post(route('events.registrations.store', $event), eventRegistrationPayload())->assertSessionHasErrors('attendees');
    $this->post(route('event-registrations.cancel', $registration))->assertSessionHasNoErrors();
});

test('unavailable Events reject whole groups without records tickets or mail', function (array $state) {
    Mail::fake();
    $event = Event::factory()->published()->create($state);
    $this->actingAs(User::factory()->create())->post(route('events.registrations.store', $event), eventRegistrationPayload(['One', 'Two']))->assertSessionHasErrors('attendees');
    expect(EventRegistration::count())->toBe(0);
    expect(EventAttendee::count())->toBe(0);
    Mail::assertNothingQueued();
})->with([
    'draft' => [['status' => EventStatus::Draft]],
    'cancelled' => [['status' => EventStatus::Cancelled]],
    'deadline closed' => [['registration_deadline' => '2026-10-14 00:00:00']],
    'already started' => [['starts_at' => '2026-10-14 00:00:00']],
    'paid' => [['price_centavos' => 100]],
    'zero capacity' => [['capacity' => 0]],
    'group too large' => [['capacity' => 1]],
]);

test('unlimited capacity accepts a group and the client cannot set count identity or ticket state', function () {
    Mail::fake();
    $event = Event::factory()->published()->create(['capacity' => null]);
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('events.registrations.store', $event), [...eventRegistrationPayload(), 'attendee_count' => 0, 'user_id' => 123])->assertSessionHasErrors(['attendee_count', 'user_id']);
    $this->post(route('events.registrations.store', $event), ['submission_key' => Str::uuid()->toString(), 'attendees' => [['name' => 'Maria', 'checked_in_at' => now()]]])->assertSessionHasErrors('attendees.0');
    $this->post(route('events.registrations.store', $event), eventRegistrationPayload(['One', 'Two', 'Three', 'Four']))->assertSessionHasNoErrors();
    expect(EventRegistration::query()->sole()->attendee_count)->toBe(4);
    expect(EventAttendee::count())->toBe(4);
});

test('registration and QR details are private and Event management does not grant registration reading', function () {
    Mail::fake();
    $event = Event::factory()->published()->create();
    $owner = User::factory()->create();
    $this->actingAs($owner)->post(route('events.registrations.store', $event), eventRegistrationPayload());
    $registration = EventRegistration::query()->sole();
    $ticket = $registration->attendees()->sole();
    $parameters = ['attendee' => $ticket->ticket_token];
    $this->get(route('event-tickets.show', $parameters))->assertOk();
    $qr = $this->get(route('event-tickets.qr', $parameters));
    $qr->assertOk()->assertHeader('Content-Type', 'image/svg+xml')->assertSee('<svg', false);
    expect($qr->headers->get('Cache-Control'))->toContain('no-store');
    $this->actingAs(User::factory()->create());
    $this->get(route('event-registrations.show', $registration))->assertNotFound();
    $this->get(route('event-tickets.show', $parameters))->assertNotFound();
    $this->get(route('event-tickets.qr', $parameters))->assertNotFound();
    $this->post(route('event-registrations.cancel', $registration))->assertNotFound();
    $this->put(route('event-attendees.update', $ticket), ['name' => 'Intruder'])->assertNotFound();
    $this->get(route('event-registrations.index'))->assertInertia(fn ($page) => $page->has('registrations', 0));
    $this->get(route('events.show', $event))->assertDontSee($ticket->ticket_token);
    $officer = User::factory()->officer()->create();
    Role::findByName('officer')->givePermissionTo('events.manage');
    $this->actingAs($officer)->get(route('event-registrations.show', $registration))->assertNotFound();
    $this->get(route('officer.event-registrations.index'))->assertForbidden();
    Role::findByName('officer')->givePermissionTo('events.registrations.read');
    $this->get(route('event-registrations.show', $registration))->assertOk();
    $this->get(route('officer.event-registrations.index'))->assertInertia(fn ($page) => $page->has('registrations', 1));
    $this->post(route('event-registrations.cancel', $registration))->assertNotFound();
});

test('whole cancellation releases every place invalidates all tickets and sends one cancellation mail', function () {
    Mail::fake();
    $event = Event::factory()->published()->create(['capacity' => 2]);
    $this->actingAs(User::factory()->create())->post(route('events.registrations.store', $event), eventRegistrationPayload(['One', 'Two']));
    $registration = EventRegistration::query()->sole();
    $tickets = $registration->attendees()->get();
    $this->post(route('event-registrations.cancel', $registration))->assertSessionHasNoErrors();
    $cancelledAt = $registration->fresh()->cancelled_at;
    $this->post(route('event-registrations.cancel', $registration))->assertSessionHasNoErrors();
    expect($registration->fresh()->cancelled_at->equalTo($cancelledAt))->toBeTrue();
    expect(app(EventRegistrationRules::class)->reservedPlaces($event))->toBe(0);
    expect($registration->attendees()->whereNull('revoked_at')->count())->toBe(0);
    foreach ($tickets as $ticket) {
        $this->get(route('event-tickets.qr', ['attendee' => $ticket->ticket_token]))->assertGone();
    }
    Mail::assertQueued(EventRegistrationCancelled::class, 1);
    $this->actingAs(User::factory()->create())->post(route('events.registrations.store', $event), eventRegistrationPayload(['New one', 'New two']))->assertSessionHasNoErrors();
    expect(app(EventRegistrationRules::class)->reservedPlaces($event))->toBe(2);
});

test('owners correct names before check in while attendee count and ticket identity stay fixed', function () {
    Mail::fake();
    $this->actingAs(User::factory()->create())->post(route('events.registrations.store', Event::factory()->published()->create()), eventRegistrationPayload(['Misspelled name']));
    $ticket = EventAttendee::query()->sole();
    $token = $ticket->ticket_token;
    $this->put(route('event-attendees.update', $ticket), ['name' => 'Corrected name'])->assertSessionHasNoErrors();
    expect($ticket->fresh()->name)->toBe('Corrected name');
    expect($ticket->fresh()->ticket_token)->toBe($token);
    expect($ticket->registration->attendee_count)->toBe(1);
    $this->put(route('event-attendees.update', $ticket), ['name' => 'Bad', 'attendee_count' => 2])->assertSessionHasErrors('attendee_count');
    $ticket->update(['checked_in_at' => now()]);
    $this->put(route('event-attendees.update', $ticket), ['name' => 'Another name'])->assertSessionHasErrors('name');
    expect($ticket->fresh()->name)->toBe('Corrected name');
});

test('Event cancellation immediately invalidates admission without exposing registration data publicly', function () {
    Mail::fake();
    $event = Event::factory()->published()->create();
    $this->actingAs(User::factory()->create())->post(route('events.registrations.store', $event), eventRegistrationPayload());
    $registration = EventRegistration::query()->sole();
    $ticket = $registration->attendees()->sole();
    $event->update(['status' => EventStatus::Cancelled]);
    $this->get(route('event-registrations.show', $registration))->assertInertia(fn ($page) => $page->where('registration.tickets.0.valid', false)->where('registration.tickets.0.qr_url', null));
    $this->get(route('event-tickets.qr', ['attendee' => $ticket->ticket_token]))->assertGone();
});

test('registration input requires a submission identity and at least one nonblank named attendee', function (array $input, string $error) {
    Mail::fake();
    $event = Event::factory()->published()->create();
    $this->actingAs(User::factory()->create())->post(route('events.registrations.store', $event), $input)->assertSessionHasErrors($error);
    expect(EventRegistration::count())->toBe(0);
    Mail::assertNothingQueued();
})->with([
    'empty submission' => [[], 'submission_key'],
    'bad submission identity' => [['submission_key' => 'invalid', 'attendees' => [['name' => 'Maria']]], 'submission_key'],
    'empty group' => [['submission_key' => '550e8400-e29b-41d4-a716-446655440000', 'attendees' => []], 'attendees'],
    'blank name' => [['submission_key' => '550e8400-e29b-41d4-a716-446655440000', 'attendees' => [['name' => ' ']]], 'attendees.0.name'],
    'long name' => [['submission_key' => '550e8400-e29b-41d4-a716-446655440000', 'attendees' => [['name' => str_repeat('a', 256)]]], 'attendees.0.name'],
]);

test('cancelled registrations reject name corrections and guest ticket requests require login', function () {
    Mail::fake();
    $user = User::factory()->create();
    $event = Event::factory()->published()->create();
    $this->actingAs($user)->post(route('events.registrations.store', $event), eventRegistrationPayload());
    $registration = EventRegistration::query()->sole();
    $ticket = $registration->attendees()->sole();
    $this->post(route('event-registrations.cancel', $registration));
    $this->put(route('event-attendees.update', $ticket), ['name' => 'Cannot change'])->assertSessionHasErrors('name');
    $this->post(route('logout'));
    $this->get(route('event-tickets.show', ['attendee' => $ticket->ticket_token]))->assertRedirect(route('login'));
    $this->get(route('event-tickets.qr', ['attendee' => $ticket->ticket_token]))->assertRedirect(route('login'));
});
