<?php

use App\Actions\Events\CheckInEventAttendee;
use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(Carbon::parse('2026-10-14 00:00:00', 'UTC'));
});

function admissionTicket(array $registration = []): EventAttendee
{
    return EventAttendee::factory()->create(['event_registration_id' => EventRegistration::factory()->create([
        'event_id' => Event::factory()->published()->create()->id, ...$registration,
    ])->id]);
}

test('check in permission is enforced on both screens and every admission request', function () {
    $ticket = admissionTicket();
    $event = $ticket->registration->event;
    $this->get(route('officer.event-check-in.index'))->assertRedirect(route('login'));
    $this->post(route('officer.event-check-in.store', $event), ['ticket' => $ticket->ticket_token])->assertRedirect(route('login'));
    $officer = User::factory()->officer()->create();
    $role = Role::findByName('officer');
    $role->syncPermissions(['events.registrations.read']);
    $this->actingAs($officer)->get(route('officer.event-check-in.index'))->assertForbidden();
    $this->get(route('officer.event-check-in.show', $event))->assertForbidden();
    $this->postJson(route('officer.event-check-in.store', $event), ['ticket' => $ticket->ticket_token])->assertForbidden();
    expect(fn () => app(CheckInEventAttendee::class)->handle($officer, $event, $ticket->ticket_token))->toThrow(HttpException::class);
    $role->syncPermissions(['events.check-in']);
    $this->get(route('officer.event-check-in.index'))->assertOk();
    $this->get(route('officer.event-check-in.show', $event))->assertOk();
    $this->postJson(route('officer.event-check-in.store', $event), ['ticket' => $ticket->ticket_token])->assertOk();
    $officer->memberships()->update(['ended_at' => now()]);
    $this->get(route('officer.event-check-in.show', $event))->assertForbidden();
    $this->postJson(route('officer.event-check-in.store', $event), ['ticket' => $ticket->ticket_token])->assertForbidden();
});

test('free and paid confirmed attendees are admitted once with actor and time retained on repeat requests', function (int $price) {
    $ticket = admissionTicket(['price_centavos' => $price, 'total_centavos' => $price]);
    $actor = User::factory()->superAdmin()->create();
    $this->actingAs($actor)->postJson(route('officer.event-check-in.store', $ticket->registration->event), ['ticket' => $ticket->ticket_token])
        ->assertOk()->assertJsonPath('outcome', 'checked-in')->assertJsonPath('name', $ticket->name);
    expect($ticket->fresh()->checked_in_by_user_id)->toBe($actor->id);
    expect($ticket->fresh()->checked_in_at->equalTo(now()))->toBeTrue();
    $this->travel(1)->hours();
    $this->actingAs(User::factory()->superAdmin()->create())->postJson(route('officer.event-check-in.store', $ticket->registration->event), ['ticket' => $ticket->ticket_token])
        ->assertOk()->assertJsonPath('outcome', 'already-checked-in')->assertJsonPath('checked_in_at', '2026-10-14T00:00:00+00:00')->assertJsonPath('checked_in_by_user_id', $actor->id);
    expect($ticket->fresh()->checked_in_by_user_id)->toBe($actor->id);
})->with(['free' => 0, 'paid' => 12550]);

test('pending expired awaiting review rejected cancelled and unconfirmed tickets cannot be admitted', function (array $state) {
    $ticket = admissionTicket($state);
    $this->actingAs(User::factory()->superAdmin()->create())->postJson(route('officer.event-check-in.store', $ticket->registration->event), ['ticket' => $ticket->ticket_token])
        ->assertUnprocessable()->assertJsonValidationErrors('ticket');
    expect($ticket->fresh()->checked_in_at)->toBeNull();
})->with([
    'pending' => [['status' => EventRegistrationStatus::PendingPayment, 'expires_at' => now()->addDay()]],
    'expired without cleanup' => [['status' => EventRegistrationStatus::PendingPayment, 'expires_at' => '2026-10-13 00:00:00']],
    'awaiting review' => [['status' => EventRegistrationStatus::AwaitingReview]],
    'expired' => [['status' => EventRegistrationStatus::Expired]],
    'rejected' => [['status' => EventRegistrationStatus::Rejected]],
    'cancelled' => [['cancelled_at' => '2026-10-14 00:00:00', 'status' => EventRegistrationStatus::Cancelled]],
    'unconfirmed' => [['confirmed_at' => null]],
]);

test('revoked wrong Event and cancelled Event tickets are refused without admission changes', function () {
    $ticket = admissionTicket();
    $this->actingAs(User::factory()->superAdmin()->create());
    $this->postJson(route('officer.event-check-in.store', Event::factory()->published()->create()), ['ticket' => $ticket->ticket_token])->assertUnprocessable()->assertJsonValidationErrors('ticket');
    $ticket->update(['revoked_at' => now()]);
    $this->postJson(route('officer.event-check-in.store', $ticket->registration->event), ['ticket' => $ticket->ticket_token])->assertUnprocessable();
    $ticket->update(['revoked_at' => null]);
    $ticket->registration->event->update(['status' => EventStatus::Cancelled]);
    $this->postJson(route('officer.event-check-in.store', $ticket->registration->event), ['ticket' => $ticket->ticket_token])->assertUnprocessable();
    expect($ticket->fresh()->checked_in_at)->toBeNull();
});

test('scanned ticket URLs resolve opaque identities while IDs unknown values and non ticket URLs are rejected', function () {
    $ticket = admissionTicket();
    $this->actingAs(User::factory()->superAdmin()->create());
    $url = route('officer.event-check-in.store', $ticket->registration->event);
    foreach ([(string) $ticket->id, str_repeat('x', 64), route('events.show', $ticket->registration->event), 'javascript:alert(1)', 'https://example.com/event-tickets/'.$ticket->ticket_token] as $invalid) {
        $this->postJson($url, ['ticket' => $invalid])->assertUnprocessable()->assertJsonValidationErrors('ticket');
    }
    $this->postJson($url, ['ticket' => route('event-tickets.show', ['attendee' => $ticket->ticket_token])])->assertOk()->assertJsonPath('outcome', 'checked-in');
    $this->postJson($url, ['ticket' => $ticket->ticket_token, 'checked_in_by_user_id' => 999, 'checked_in_at' => '2000-01-01'])->assertUnprocessable();
});

test('manual lookup searches attendee and registering account names within the selected Event with bounded results', function () {
    $ticket = admissionTicket();
    $ticket->update(['name' => 'Maria Santos']);
    $ticket->registration->user->update(['name' => 'Jose Santos']);
    $other = admissionTicket();
    $other->update(['name' => 'Maria Other Event']);
    $this->actingAs(User::factory()->superAdmin()->create());
    $url = route('officer.event-check-in.show', $ticket->registration->event);
    $this->get($url.'?search=Maria')->assertInertia(fn (Assert $page) => $page->component('officer/event-check-in/Show')->has('attendees.data', 1)
        ->where('attendees.data.0.name', 'Maria Santos')->where('attendees.data.0.ticket', $ticket->ticket_token)->where('attendees.data.0.valid', true));
    $this->get($url.'?search=Jose')->assertInertia(fn (Assert $page) => $page->has('attendees.data', 1));
    $this->get($url.'?search=Missing')->assertInertia(fn (Assert $page) => $page->has('attendees.data', 0));
    EventAttendee::factory()->count(51)->create(['event_registration_id' => $ticket->event_registration_id]);
    $this->get($url)->assertInertia(fn (Assert $page) => $page->has('attendees.data', 50)->where('attendees.total', 52)->where('attendees.last_page', 2));
    $this->get($url.'?page=2')->assertInertia(fn (Assert $page) => $page->has('attendees.data', 2));
});

test('public Event lists never expose ticket credentials and check in does not permit name changes after admission', function () {
    $ticket = admissionTicket();
    $this->get(route('events.index'))->assertDontSee($ticket->ticket_token);
    $this->actingAs(User::factory()->superAdmin()->create())->postJson(route('officer.event-check-in.store', $ticket->registration->event), ['ticket' => $ticket->ticket_token])->assertOk();
    $this->actingAs($ticket->registration->user)->put(route('event-attendees.update', $ticket), ['name' => 'New name'])->assertSessionHasErrors('name');
    expect($ticket->fresh()->name)->toBe($ticket->name);
});
