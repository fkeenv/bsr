<?php

use App\Actions\Events\CheckInEventAttendee;
use App\Actions\Events\ExpireEventReservation;
use App\Actions\Events\RegisterForEvent;
use App\Actions\Events\RescheduleEvent;
use App\Actions\Events\SubmitEventPayment;
use App\Enums\EventStatus;
use App\Mail\EventRegistrationUpdated;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\EventPaymentRecord;
use App\Models\EventRefundRecord;
use App\Models\EventRegistration;
use App\Models\EventScheduleChange;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\User;
use App\Support\EventRegistrationRules;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(Carbon::parse('2026-10-16 00:00:00', 'UTC'));
    Mail::fake();
});

function changedEventSchedule(): array
{
    return ['starts_at' => '2027-02-20T16:00', 'ends_at' => '2027-02-20T18:00', 'registration_deadline' => '2027-02-19T12:00'];
}

test('staff reschedule published Events repeatedly with complete schedule history and unchanged ticket identities', function () {
    $actor = User::factory()->superAdmin()->create();
    $event = Event::factory()->published()->create(['starts_at' => '2027-01-20 08:00:00', 'ends_at' => '2027-01-20 10:00:00', 'registration_deadline' => '2027-01-19 04:00:00']);
    $registration = app(RegisterForEvent::class)->handle(User::factory()->create(), $event, ['submission_key' => Str::uuid()->toString(), 'attendees' => [['name' => 'Ana']]]);
    $token = $registration->attendees()->sole()->ticket_token;
    $this->actingAs($actor);

    $this->post('/officer/events/'.$event->id.'/reschedule', changedEventSchedule())->assertSessionHasNoErrors()->assertRedirect();

    expect($event->fresh()->status->value)->toBe('rescheduled');
    $change = EventScheduleChange::query()->sole();
    expect($change->old_starts_at->toDateTimeString())->toBe('2027-01-20 08:00:00');
    expect($change->new_starts_at->toDateTimeString())->toBe('2027-02-20 08:00:00');
    expect($change->old_registration_deadline->toDateTimeString())->toBe('2027-01-19 04:00:00');
    expect($change->new_registration_deadline->toDateTimeString())->toBe('2027-02-19 04:00:00');
    expect($change->changed_by_user_id)->toBe($actor->id);
    expect($change->created_at->toDateTimeString())->toBe('2026-10-16 00:00:00');
    $this->get(route('events.show', $event))->assertInertia(fn (Assert $page) => $page->where('event.status', 'rescheduled')->where('registrationAvailability', null));
    $this->get(route('event-registrations.show', $registration))->assertInertia(fn (Assert $page) => $page->where('registration.tickets.0.valid', true)->where('registration.event.starts_at', '2027-02-20T08:00:00+00:00'));
    $this->post('/officer/events/'.$event->id.'/reschedule', [...changedEventSchedule(), 'starts_at' => '2027-02-21T16:00', 'ends_at' => '2027-02-21T18:00'])->assertSessionHasNoErrors();
    expect(EventScheduleChange::count())->toBe(2);
    expect(EventScheduleChange::query()->latest('id')->first()->old_starts_at->toDateTimeString())->toBe('2027-02-20 08:00:00');
    expect($registration->attendees()->sole()->ticket_token)->toBe($token);
    Mail::assertQueued(EventRegistrationUpdated::class, fn ($mail) => $mail->headline === 'Your Event was rescheduled' && str_contains($mail->message, 'February 20, 2027') && $mail->afterCommit);
    Mail::assertQueued(EventRegistrationUpdated::class, 2);
});

test('whole Event cancellation resolves reservations preserves payments and check in history and queues notifications only once', function () {
    $actor = User::factory()->superAdmin()->create();
    $event = Event::factory()->published()->create(['price_centavos' => 10000, 'payment_instructions' => 'Pay at the office.']);
    $registrations = collect(['confirmed', 'pending-payment', 'awaiting-review'])->map(function (string $status) use ($event) {
        $registration = EventRegistration::factory()->create(['event_id' => $event->id, 'status' => $status, 'price_centavos' => 10000, 'total_centavos' => 10000, 'confirmed_at' => $status === 'confirmed' ? now() : null, 'expires_at' => now()->addHours(24)]);
        EventAttendee::factory()->create(['event_registration_id' => $registration->id]);
        if ($status !== 'pending-payment') {
            EventPaymentRecord::factory()->create(['event_registration_id' => $registration->id, 'status' => $status === 'confirmed' ? 'confirmed' : 'submitted']);
        }

        return $registration;
    });
    $confirmed = $registrations[0];
    $attendee = $confirmed->attendees()->sole();
    app(CheckInEventAttendee::class)->handle($actor, $event, $attendee->ticket_token);
    $this->actingAs($actor);

    $this->post(route('officer.events.cancel', $event))->assertSessionHasNoErrors();

    expect($event->fresh()->status)->toBe(EventStatus::Cancelled);
    foreach ($registrations as $registration) {
        expect($registration->fresh()->status->value)->toBe('cancelled');
        expect($registration->attendees()->sole()->revoked_at)->not->toBeNull();
    }
    expect($attendee->fresh()->checked_in_by_user_id)->toBe($actor->id);
    expect($attendee->fresh()->ticket_token)->toBe($attendee->ticket_token);
    expect(EventPaymentRecord::query()->orderBy('id')->pluck('status')->all())->toBe(['confirmed', 'submitted']);
    expect(EventRefundRecord::count())->toBe(0);
    $this->get(route('event-registrations.show', $confirmed))->assertInertia(fn (Assert $page) => $page
        ->where('financial.payment.refund_due_centavos', 10000)->where('financial.payment.refunded_centavos', 0)->where('registration.tickets.0.valid', false));
    $this->post(route('officer.event-check-in.store', $event), ['ticket' => $attendee->ticket_token])->assertSessionHasErrors('ticket');
    $this->actingAs($registrations[1]->user)->post(route('event-registrations.payment', $registrations[1]), ['method' => 'cash', 'reference' => 'Late payment'])->assertSessionHasErrors('payment');
    $this->post(route('events.registrations.store', $event), ['submission_key' => Str::uuid()->toString(), 'attendees' => [['name' => 'Late']]])->assertSessionHasErrors('attendees');
    $this->actingAs($actor)->post(route('officer.events.cancel', $event))->assertSessionHasNoErrors();
    Mail::assertQueued(EventRegistrationUpdated::class, 3);
    Mail::assertQueued(EventRegistrationUpdated::class, fn ($mail) => $mail->headline === 'Your Event was cancelled' && $mail->afterCommit);
});

test('rescheduling validates a future Manila schedule and deadline without changing history or notifying anyone on failure', function (array $invalid, string $error) {
    $event = Event::factory()->published()->create();
    $this->actingAs(User::factory()->superAdmin()->create());

    $this->post(route('officer.events.reschedule', $event), [...changedEventSchedule(), ...$invalid])->assertSessionHasErrors($error);

    expect($event->fresh()->status)->toBe(EventStatus::Published);
    expect(EventScheduleChange::count())->toBe(0);
    Mail::assertNothingQueued();
})->with([
    [['starts_at' => ''], 'starts_at'], [['starts_at' => 'invalid'], 'starts_at'],
    [['starts_at' => '2026-10-15T16:00', 'ends_at' => '2026-10-15T18:00', 'registration_deadline' => '2026-10-15T12:00'], 'starts_at'],
    [['ends_at' => '2027-02-20T15:00'], 'ends_at'], [['ends_at' => '2027-02-20T16:00'], 'ends_at'],
    [['registration_deadline' => '2027-02-21T12:00'], 'registration_deadline'],
    [['registration_deadline' => '2026-10-16T08:00'], 'registration_deadline'],
]);

test('rescheduling requires independent management permission and cannot revive drafts or cancelled Events', function () {
    $event = Event::factory()->published()->create();
    $this->post(route('officer.events.reschedule', $event), changedEventSchedule())->assertRedirect(route('login'));
    $officer = User::factory()->officer()->create();
    $role = Role::findByName('officer');
    $role->syncPermissions(['events.check-in', 'events.payments.review', 'events.refunds.review']);
    $this->actingAs($officer)->post(route('officer.events.reschedule', $event), changedEventSchedule())->assertForbidden();
    expect(fn () => app(RescheduleEvent::class)->handle($officer, $event, changedEventSchedule()))->toThrow(HttpException::class);
    $role->givePermissionTo('events.manage');
    $this->post(route('officer.events.reschedule', $event), changedEventSchedule())->assertSessionHasNoErrors();
    $this->post(route('officer.events.reschedule', $event), changedEventSchedule())->assertSessionHasErrors('starts_at');
    foreach (['draft', 'cancelled'] as $status) {
        $other = Event::factory()->create(['status' => $status]);
        $this->post(route('officer.events.reschedule', $other), changedEventSchedule())->assertSessionHasErrors('event');
    }
    expect(EventScheduleChange::count())->toBe(1);
    Mail::assertNothingQueued();
});

test('rescheduling carries paid reservations and review submissions without extending expiry and retains locks and check in discovery', function () {
    $event = Event::factory()->published()->create(['price_centavos' => 10000, 'payment_instructions' => 'Pay the office.', 'capacity' => 2]);
    $register = fn () => app(RegisterForEvent::class)->handle(User::factory()->create(), $event, ['submission_key' => Str::uuid()->toString(), 'attendees' => [['name' => 'Ana']]]);
    $pending = $register();
    $review = $register();
    $payment = app(SubmitEventPayment::class)->handle($review->user, $review, ['method' => 'cash', 'reference' => 'Original cash receipt']);
    $expiry = $pending->expires_at->toIso8601String();
    $this->travel(23)->hours();
    $actor = User::factory()->superAdmin()->create();
    $this->actingAs($actor)->post(route('officer.events.reschedule', $event), [...changedEventSchedule(), 'price_centavos' => 0, 'audience' => 'members-only', 'capacity' => 0])->assertSessionHasNoErrors();
    $event->refresh();
    expect($event->price_centavos)->toBe(10000)->and($event->capacity)->toBe(2)->and($event->audience->value)->toBe('public');
    expect($pending->fresh()->expires_at->toIso8601String())->toBe($expiry);
    expect($review->fresh()->status->value)->toBe('awaiting-review');
    expect($payment->fresh()->reference)->toBe('Original cash receipt');
    $this->get(route('officer.event-check-in.index'))->assertInertia(fn (Assert $page) => $page->where('events.0.id', $event->id));
    $this->get(route('officer.event-check-in.show', $event))->assertOk();
    $this->actingAs(User::factory()->create())->post(route('events.registrations.store', $event), ['submission_key' => Str::uuid()->toString(), 'attendees' => [['name' => 'No space']]])->assertSessionHasErrors('attendees');
    $details = ['title' => $event->title, 'description' => $event->description, 'venue' => $event->venue, ...changedEventSchedule(), 'price_centavos' => 10000, 'payment_instructions' => 'Pay the office.', 'audience' => 'public', 'capacity' => 1];
    $this->actingAs($actor)->put(route('officer.events.update', $event), $details)->assertSessionHasErrors('capacity');
    $this->put(route('officer.events.update', $event), [...$details, 'capacity' => 2, 'price_centavos' => 0])->assertSessionHasErrors('price_centavos');
    $this->put(route('officer.events.update', $event), [...$details, 'capacity' => 2, 'audience' => 'members-only'])->assertSessionHasErrors('audience');
    $this->put(route('officer.events.update', $event), [...$details, 'capacity' => 2, 'starts_at' => '2027-02-20T17:00'])->assertSessionHasErrors('starts_at');
    $this->post(route('officer.events.publish', $event))->assertSessionHasNoErrors();
    expect($event->fresh()->status->value)->toBe('rescheduled');
    $this->travel(2)->hours();
    app(ExpireEventReservation::class)->handle($pending);
    expect($pending->fresh()->status->value)->toBe('expired');
    expect(app(EventRegistrationRules::class)->reservedPlaces($event))->toBe(1);
    $this->post(route('officer.event-payments.review', $payment), ['decision' => 'confirmed'])->assertSessionHasNoErrors();
    expect($review->fresh()->attendees()->sole()->ticket_token)->not->toBeNull();
    $this->post(route('officer.event-check-in.store', $event), ['ticket' => $review->attendees()->sole()->ticket_token])->assertSessionHasNoErrors();
});

test('rescheduled paid registrations retain cancellation access after Membership loss and show refunds owed separately from money returned', function () {
    $account = User::factory()->create();
    $membership = Membership::factory()->create(['user_id' => $account->id]);
    $event = Event::factory()->published()->create(['audience' => 'members-only', 'price_centavos' => 10000, 'payment_instructions' => 'Pay the office.']);
    $registration = app(RegisterForEvent::class)->handle($account, $event, ['submission_key' => Str::uuid()->toString(), 'attendees' => [['name' => 'Ana']]]);
    $payment = app(SubmitEventPayment::class)->handle($account, $registration, ['method' => 'cash', 'reference' => 'Receipt 1']);
    $actor = User::factory()->superAdmin()->create();
    $this->actingAs($actor)->post(route('officer.event-payments.review', $payment), ['decision' => 'confirmed'])->assertSessionHasNoErrors();
    $this->post(route('officer.events.reschedule', $event), changedEventSchedule())->assertSessionHasNoErrors();
    $membership->update(['ended_at' => now()]);
    $this->actingAs($account);
    $this->get(route('events.show', $event))->assertNotFound();
    $this->post(route('events.registrations.store', $event), ['submission_key' => Str::uuid()->toString(), 'attendees' => [['name' => 'Other']]])->assertSessionHasErrors('attendees');
    $this->get(route('event-registrations.show', $registration))->assertInertia(fn (Assert $page) => $page->where('financial.full_refund_eligible', true));

    $this->post(route('event-registrations.request-cancellation', $registration), ['submission_key' => Str::uuid()->toString(), 'reason' => 'Cannot attend the new date.'])->assertSessionHasNoErrors();

    $request = $registration->cancellationRequests()->sole();
    $this->actingAs($actor)->post(route('officer.event-cancellations.resolve', $request), ['decision' => 'approved', 'reason' => 'Rescheduled Event full refund.'])->assertSessionHasNoErrors();
    $this->get(route('event-registrations.show', $registration))->assertInertia(fn (Assert $page) => $page->where('financial.payment.refund_due_centavos', 10000)->where('financial.payment.refunded_centavos', 0));
    $refund = ['submission_key' => Str::uuid()->toString(), 'amount_centavos' => 10000, 'method' => 'cash', 'reference' => 'External refund 1', 'reason' => 'Full refund after rescheduling.', 'refunded_at' => '2026-10-16T08:00'];
    $this->post(route('officer.event-payments.refund', $payment), $refund)->assertSessionHasNoErrors();
    $this->get(route('event-registrations.show', $registration))->assertInertia(fn (Assert $page) => $page->where('financial.payment.refund_due_centavos', 0)->where('financial.payment.refunded_centavos', 10000));
    expect($payment->fresh()->status)->toBe('confirmed')->and($payment->fresh()->reference)->toBe('Receipt 1');
    expect(Payment::count())->toBe(0);
    expect(PaymentAllocation::count())->toBe(0);
    Mail::assertQueued(EventRegistrationUpdated::class, fn ($mail) => $mail->headline === 'Your Event cancellation was approved');
    Mail::assertQueued(EventRegistrationUpdated::class, fn ($mail) => $mail->headline === 'An Event refund was recorded');
});

test('cancelling an Event preserves expired rejected and refunded history and resolves outstanding cancellation requests', function () {
    $actor = User::factory()->superAdmin()->create();
    $event = Event::factory()->published()->create();
    $cancelled = EventRegistration::factory()->create(['event_id' => $event->id, 'status' => 'cancelled', 'cancelled_at' => now()->subDay(), 'price_centavos' => 10000]);
    $payment = EventPaymentRecord::factory()->create(['event_registration_id' => $cancelled->id, 'status' => 'confirmed']);
    $refund = EventRefundRecord::factory()->create(['event_payment_record_id' => $payment->id, 'amount_centavos' => 4000]);
    $expired = EventRegistration::factory()->create(['event_id' => $event->id, 'status' => 'expired', 'expired_at' => now()->subHour(), 'cancelled_at' => now()->subHour()]);
    $rejected = EventRegistration::factory()->create(['event_id' => $event->id, 'status' => 'rejected', 'cancelled_at' => now()->subHour()]);
    $awaiting = EventRegistration::factory()->create(['event_id' => $event->id, 'status' => 'awaiting-review', 'price_centavos' => 10000]);
    $submitted = EventPaymentRecord::factory()->create(['event_registration_id' => $awaiting->id]);
    $request = $awaiting->cancellationRequests()->create(['submission_key' => Str::uuid()->toString(), 'reason' => 'Cannot attend.']);
    $this->actingAs($actor);

    $this->post(route('officer.events.cancel', $event))->assertSessionHasNoErrors();

    expect($expired->fresh()->status->value)->toBe('expired');
    expect($rejected->fresh()->status->value)->toBe('rejected');
    expect($cancelled->fresh()->cancelled_at->toIso8601String())->toBe('2026-10-15T00:00:00+00:00');
    expect($refund->fresh()->amount_centavos)->toBe(4000);
    expect($request->fresh()->status)->toBe('approved')->and($request->fresh()->resolved_by_user_id)->toBe($actor->id);
    $this->get(route('event-registrations.show', $cancelled))->assertInertia(fn (Assert $page) => $page->where('financial.payment.refund_due_centavos', 6000)->where('financial.payment.refunded_centavos', 4000));
    $this->post(route('officer.event-payments.review', $submitted), ['decision' => 'confirmed'])->assertSessionHasNoErrors();
    expect($awaiting->fresh()->status->value)->toBe('cancelled');
    $this->get(route('event-registrations.show', $awaiting))->assertInertia(fn (Assert $page) => $page->where('financial.payment.refund_due_centavos', 10000));
});

test('the schedule audit is available to Event managers and does not leak through the public Event page', function () {
    $actor = User::factory()->superAdmin()->create();
    $event = Event::factory()->published()->create();
    $this->actingAs($actor)->post(route('officer.events.reschedule', $event), changedEventSchedule())->assertSessionHasNoErrors();

    $this->get(route('officer.events.edit', $event))->assertInertia(fn (Assert $page) => $page
        ->has('scheduleChanges', 1)->where('scheduleChanges.0.changed_by_user_id', $actor->id)
        ->where('scheduleChanges.0.new_ends_at', '2027-02-20T10:00:00+00:00')
        ->where('scheduleChanges.0.new_registration_deadline', '2027-02-19T04:00:00+00:00'));
    $this->get(route('events.show', $event))->assertInertia(fn (Assert $page) => $page->missing('scheduleChanges')->missing('event.changed_by_user_id'));
    $this->actingAs(User::factory()->create())->get(route('officer.events.edit', $event))->assertForbidden();
    Mail::assertNothingQueued();
});
