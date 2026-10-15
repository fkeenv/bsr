<?php

use App\Actions\Events\ExpireEventReservation;
use App\Actions\Events\ListEventFinances;
use App\Actions\Events\RegisterForEvent;
use App\Actions\Events\SubmitEventPayment;
use App\Actions\Payments\ConfirmPayment;
use App\Enums\EventRegistrationStatus;
use App\Mail\EventRegistrationConfirmed;
use App\Mail\EventRegistrationUpdated;
use App\Models\Charge;
use App\Models\ChargeLine;
use App\Models\Event;
use App\Models\EventPaymentRecord;
use App\Models\EventRefundRecord;
use App\Models\EventRegistration;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Property;
use App\Models\User;
use App\Support\EventRegistrationRules;
use App\Support\PropertyBalances;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(Carbon::parse('2026-10-14 00:00:00', 'UTC'));
    Mail::fake();
});

function reservePaidEvent(?User $user = null, int $count = 2): EventRegistration
{
    $event = Event::factory()->published()->create(['capacity' => 3, 'price_centavos' => 12550, 'payment_instructions' => 'Send GCash to the association or pay at the office.']);

    return app(RegisterForEvent::class)->handle($user ?? User::factory()->create(), $event, [
        'submission_key' => Str::uuid()->toString(), 'attendees' => array_fill(0, $count, ['name' => 'Maria Santos']),
    ]);
}

function paidEventSubmission(): array
{
    return ['method' => 'gcash', 'reference' => 'GCash transaction 123'];
}

function submittedEventPayment(EventRegistration $registration): EventPaymentRecord
{
    return app(SubmitEventPayment::class)->handle($registration->user, $registration, paidEventSubmission())->fresh();
}

function eventRefundPayload(int $amount = 25100): array
{
    return ['submission_key' => Str::uuid()->toString(), 'amount_centavos' => $amount, 'method' => 'bank-transfer',
        'reference' => 'Bank refund 987', 'reason' => 'Approved cancellation', 'refunded_at' => '2026-10-14T08:00'];
}

test('paid Events require instructions and support fixed PHP prices while keeping price and audience locked', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $data = ['title' => 'Paid gathering', 'description' => '<p>Join us.</p>', 'venue' => 'Clubhouse', 'starts_at' => '2027-01-20T16:00', 'ends_at' => '2027-01-20T18:00', 'registration_deadline' => '2027-01-19T12:00', 'audience' => 'public', 'capacity' => 10, 'price_centavos' => 12550];
    $this->post(route('officer.events.store'), $data)->assertSessionHasErrors('payment_instructions');
    $data['payment_instructions'] = 'Pay at the office.';
    $this->post(route('officer.events.store'), $data)->assertSessionHasNoErrors();
    $event = Event::query()->sole();
    $this->post(route('officer.events.publish', $event))->assertSessionHasNoErrors();
    app(RegisterForEvent::class)->handle(User::factory()->create(), $event, ['submission_key' => Str::uuid()->toString(), 'attendees' => [['name' => 'Ana']]]);
    $this->put(route('officer.events.update', $event), [...$data, 'price_centavos' => 100])->assertSessionHasErrors('price_centavos');
    $this->put(route('officer.events.update', $event), [...$data, 'audience' => 'members-only'])->assertSessionHasErrors('audience');
    $this->put(route('officer.events.update', $event), [...$data, 'payment_instructions' => 'New account instructions.'])->assertSessionHasNoErrors();
    expect(EventRegistration::query()->sole()->payment_instructions)->toBe('Pay at the office.');
});

test('paid registrations snapshot totals reserve exactly 24 hours and issue no tickets before confirmation', function () {
    $registration = reservePaidEvent();
    expect($registration->status)->toBe(EventRegistrationStatus::PendingPayment);
    expect($registration->price_centavos)->toBe(12550)->and($registration->total_centavos)->toBe(25100);
    expect($registration->expires_at->equalTo(now()->addHours(24)))->toBeTrue();
    expect($registration->confirmed_at)->toBeNull()->and($registration->attendees->pluck('ticket_token')->all())->toBe([null, null]);
    expect(app(EventRegistrationRules::class)->reservedPlaces($registration->event))->toBe(2);
    $this->actingAs($registration->user)->get(route('event-registrations.show', $registration))->assertInertia(fn (Assert $page) => $page
        ->where('registration.status', 'pending-payment')->where('registration.total_centavos', 25100)->where('registration.tickets.0.ticket_url', null)->where('registration.tickets.0.qr_url', null));
    Mail::assertQueued(EventRegistrationUpdated::class, fn ($mail) => $mail->headline === 'Your Event places are reserved' && $mail->afterCommit);
    Mail::assertNotQueued(EventRegistrationConfirmed::class);
});

test('expired unpaid places stop consuming capacity before cleanup and expire notification is sent once', function () {
    $registration = reservePaidEvent();
    $this->travel(24)->hours();
    expect(app(EventRegistrationRules::class)->reservedPlaces($registration->event))->toBe(0);
    expect(app(EventRegistrationRules::class)->effectiveStatus($registration))->toBe(EventRegistrationStatus::Expired);
    expect($registration->fresh()->status)->toBe(EventRegistrationStatus::PendingPayment);
    $this->artisan('events:expire-reservations')->expectsOutput('Expired 1 reservations.')->assertSuccessful();
    $this->artisan('events:expire-reservations')->expectsOutput('Expired 0 reservations.')->assertSuccessful();
    expect($registration->fresh()->status)->toBe(EventRegistrationStatus::Expired);
    Mail::assertQueued(EventRegistrationUpdated::class, fn ($mail) => $mail->headline === 'Your Event reservation expired');
    Mail::assertQueuedCount(2);
});

test('an expired payment submission persists expiry while returning a validation error', function () {
    $registration = reservePaidEvent();
    $this->travel(24)->hours();
    $this->actingAs($registration->user)->post(route('event-registrations.payment', $registration), paidEventSubmission())->assertSessionHasErrors('payment');
    expect($registration->fresh()->status)->toBe(EventRegistrationStatus::Expired);
    expect(EventPaymentRecord::count())->toBe(0);
    Mail::assertQueuedCount(2);
});

test('submitted payments retain places beyond 24 hours and cannot be overwritten or submitted twice', function () {
    $registration = reservePaidEvent();
    $this->travel(23)->hours();
    $this->actingAs($registration->user);
    $this->post(route('event-registrations.payment', $registration), paidEventSubmission())->assertSessionHasNoErrors();
    $payment = EventPaymentRecord::query()->sole();
    expect($payment->amount_centavos)->toBe(25100)->and($registration->fresh()->status)->toBe(EventRegistrationStatus::AwaitingReview);
    $this->post(route('event-registrations.payment', $registration), paidEventSubmission())->assertSessionHasNoErrors();
    $this->post(route('event-registrations.payment', $registration), [...paidEventSubmission(), 'reference' => 'Other'])->assertSessionHasErrors('reference');
    expect(EventPaymentRecord::count())->toBe(1);
    $this->travel(48)->hours();
    expect(app(ExpireEventReservation::class)->handle($registration))->toBeFalse();
    expect(app(EventRegistrationRules::class)->reservedPlaces($registration->event))->toBe(2);
    expect(app(EventRegistrationRules::class)->ticketIsValid($registration->attendees->first()))->toBeFalse();
});

test('payment submission enforces ownership fixed totals supported methods and image receipts', function () {
    Storage::fake('local');
    $registration = reservePaidEvent();
    $this->actingAs(User::factory()->create())->post(route('event-registrations.payment', $registration), paidEventSubmission())->assertNotFound();
    $this->actingAs(User::factory()->superAdmin()->create())->post(route('event-registrations.payment', $registration), paidEventSubmission())->assertNotFound();
    $this->actingAs($registration->user);
    foreach ([['amount_centavos' => 1], ['method' => 'card'], ['reference' => ''], ['receipt' => UploadedFile::fake()->create('fake.pdf', 10, 'application/pdf')]] as $invalid) {
        $this->post(route('event-registrations.payment', $registration), [...paidEventSubmission(), ...$invalid])->assertSessionHasErrors(array_key_first($invalid));
    }
    $this->post(route('event-registrations.payment', $registration), [...paidEventSubmission(), 'receipt' => UploadedFile::fake()->image('receipt.png')])->assertSessionHasNoErrors();
    Storage::disk('local')->assertExists(EventPaymentRecord::query()->sole()->receipt_path);
});

test('financial receipts and details are private to the owner and financial reviewers with independent grants', function () {
    Storage::fake('local');
    $registration = reservePaidEvent();
    $this->actingAs($registration->user)->post(route('event-registrations.payment', $registration), [...paidEventSubmission(), 'receipt' => UploadedFile::fake()->image('receipt.png')]);
    $payment = EventPaymentRecord::query()->sole();
    $url = route('event-payments.receipt', $payment);
    $this->get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    $this->actingAs(User::factory()->create())->get($url)->assertNotFound();
    $officer = User::factory()->officer()->create();
    $role = Role::findByName('officer');
    $role->syncPermissions(['events.registrations.read']);
    $this->actingAs($officer)->get($url)->assertNotFound();
    $this->get(route('event-registrations.show', $registration))->assertInertia(fn (Assert $page) => $page->where('financial', null));
    $this->get(route('officer.event-finances.index'))->assertForbidden();
    expect(fn () => app(ListEventFinances::class)->handle($officer))->toThrow(HttpException::class);
    $role->syncPermissions(['events.payments.review']);
    $this->get($url)->assertOk();
    $this->get(route('officer.event-finances.index'))->assertInertia(fn (Assert $page) => $page->where('canReviewPayments', true)->where('canReviewRefunds', false));
    $this->post(route('officer.event-payments.refund', $payment), eventRefundPayload())->assertForbidden();
    $role->syncPermissions(['events.refunds.review']);
    $this->get($url)->assertOk();
    $this->post(route('officer.event-payments.review', $payment), ['decision' => 'confirmed'])->assertForbidden();
    $officer->memberships()->update(['ended_at' => now()]);
    $this->get($url)->assertNotFound();
    $this->get(route('officer.event-finances.index'))->assertForbidden();
});

test('only confirmation issues valid tickets and duplicate review never changes their identities', function () {
    $registration = reservePaidEvent();
    $payment = submittedEventPayment($registration);
    $reviewer = User::factory()->superAdmin()->create();
    $this->actingAs($registration->user)->post(route('officer.event-payments.review', $payment), ['decision' => 'confirmed'])->assertForbidden();
    $this->actingAs($reviewer)->post(route('officer.event-payments.review', $payment), ['decision' => 'confirmed'])->assertSessionHasNoErrors();
    $tokens = $registration->attendees()->pluck('ticket_token')->all();
    expect(array_unique($tokens))->toHaveCount(2);
    foreach ($registration->fresh()->attendees as $ticket) {
        expect(app(EventRegistrationRules::class)->ticketIsValid($ticket))->toBeTrue();
    }
    $this->post(route('officer.event-payments.review', $payment), ['decision' => 'confirmed'])->assertSessionHasNoErrors();
    expect($registration->attendees()->pluck('ticket_token')->all())->toBe($tokens);
    expect($payment->fresh()->reviewed_by_user_id)->toBe($reviewer->id);
    $this->post(route('officer.event-payments.review', $payment), ['decision' => 'rejected', 'reason' => 'Changed my mind'])->assertSessionHasErrors('payment');
    Mail::assertQueued(EventRegistrationConfirmed::class, 1);
});

test('rejection requires a reason releases all places retains payment history and permits a new registration', function () {
    $registration = reservePaidEvent();
    $payment = submittedEventPayment($registration);
    $this->actingAs(User::factory()->superAdmin()->create());
    $this->post(route('officer.event-payments.review', $payment), ['decision' => 'rejected'])->assertSessionHasErrors('reason');
    $this->post(route('officer.event-payments.review', $payment), ['decision' => 'rejected', 'reason' => 'Receipt could not be verified'])->assertSessionHasNoErrors();
    $this->post(route('officer.event-payments.review', $payment), ['decision' => 'rejected', 'reason' => 'Receipt could not be verified'])->assertSessionHasNoErrors();
    expect($registration->fresh()->status)->toBe(EventRegistrationStatus::Rejected);
    expect(app(EventRegistrationRules::class)->reservedPlaces($registration->event))->toBe(0);
    expect($payment->fresh()->review_reason)->toBe('Receipt could not be verified');
    $retry = app(RegisterForEvent::class)->handle($registration->user, $registration->event, ['submission_key' => Str::uuid()->toString(), 'attendees' => [['name' => 'Retry']]]);
    expect($retry->id)->not->toBe($registration->id);
    expect(EventPaymentRecord::count())->toBe(1);
    Mail::assertNotQueued(EventRegistrationConfirmed::class);
});

test('unpaid cancellation is immediate but submitted and confirmed payments require whole registration requests', function (bool $confirm) {
    $unpaid = reservePaidEvent();
    $this->actingAs($unpaid->user)->post(route('event-registrations.cancel', $unpaid))->assertSessionHasNoErrors();
    expect(app(EventRegistrationRules::class)->reservedPlaces($unpaid->event))->toBe(0);
    $registration = reservePaidEvent();
    $payment = submittedEventPayment($registration);
    if ($confirm) {
        $this->actingAs(User::factory()->superAdmin()->create())->post(route('officer.event-payments.review', $payment), ['decision' => 'confirmed']);
    }
    $this->actingAs($registration->user)->post(route('event-registrations.cancel', $registration))->assertSessionHasErrors('registration');
    $payload = ['submission_key' => Str::uuid()->toString(), 'reason' => 'Cannot attend'];
    $this->post(route('event-registrations.request-cancellation', $registration), $payload)->assertSessionHasNoErrors();
    $this->post(route('event-registrations.request-cancellation', $registration), $payload)->assertSessionHasNoErrors();
    expect($registration->cancellationRequests()->count())->toBe(1);
    $cancellation = $registration->cancellationRequests()->sole();
    expect(app(EventRegistrationRules::class)->reservedPlaces($registration->event))->toBe(2);
    $this->actingAs(User::factory()->superAdmin()->create())->post(route('officer.event-cancellations.resolve', $cancellation), ['decision' => 'approved', 'reason' => 'Approved by HOA'])->assertSessionHasNoErrors();
    $this->post(route('officer.event-cancellations.resolve', $cancellation), ['decision' => 'approved', 'reason' => 'Approved by HOA'])->assertSessionHasNoErrors();
    expect(app(EventRegistrationRules::class)->reservedPlaces($registration->event))->toBe(0);
    expect($registration->fresh()->status)->toBe(EventRegistrationStatus::Cancelled);
    foreach ($registration->fresh()->attendees as $ticket) {
        expect(app(EventRegistrationRules::class)->ticketIsValid($ticket))->toBeFalse();
    }
    expect(EventRefundRecord::count())->toBe(0);
    expect($payment->fresh()->status)->toBe($confirm ? 'confirmed' : 'submitted');
})->with([false, true]);

test('declined cancellation preserves tickets and retains history when the owner requests again', function () {
    $registration = reservePaidEvent();
    $payment = submittedEventPayment($registration);
    $reviewer = User::factory()->superAdmin()->create();
    $this->actingAs($reviewer)->post(route('officer.event-payments.review', $payment), ['decision' => 'confirmed']);
    $payload = ['submission_key' => Str::uuid()->toString(), 'reason' => 'Please cancel'];
    $this->actingAs($registration->user)->post(route('event-registrations.request-cancellation', $registration), $payload);
    $cancellation = $registration->cancellationRequests()->sole();
    $this->actingAs($reviewer)->post(route('officer.event-cancellations.resolve', $cancellation), ['decision' => 'declined', 'reason' => 'Please contact the office'])->assertSessionHasNoErrors();
    expect(app(EventRegistrationRules::class)->ticketIsValid($registration->fresh()->attendees->first()))->toBeTrue();
    $this->actingAs($registration->user)->post(route('event-registrations.request-cancellation', $registration), [...$payload, 'submission_key' => Str::uuid()->toString()])->assertSessionHasNoErrors();
    expect($registration->cancellationRequests()->count())->toBe(2);
});

test('external refunds preserve the payment require cancelled confirmed registrations and cannot overrefund or duplicate', function () {
    $registration = reservePaidEvent();
    $payment = submittedEventPayment($registration);
    $reviewer = User::factory()->superAdmin()->create();
    $this->actingAs($reviewer);
    $payload = eventRefundPayload(10000);
    $this->post(route('officer.event-payments.refund', $payment), $payload)->assertSessionHasErrors('refund');
    $this->post(route('officer.event-payments.review', $payment), ['decision' => 'confirmed']);
    $this->post(route('officer.event-payments.refund', $payment), $payload)->assertSessionHasErrors('refund');
    $this->actingAs($registration->user)->post(route('event-registrations.request-cancellation', $registration), ['submission_key' => Str::uuid()->toString(), 'reason' => 'Cannot attend']);
    $this->actingAs($reviewer)->post(route('officer.event-cancellations.resolve', $registration->cancellationRequests()->sole()), ['decision' => 'approved', 'reason' => 'Approved']);
    $this->post(route('officer.event-payments.refund', $payment), $payload)->assertSessionHasNoErrors();
    $this->post(route('officer.event-payments.refund', $payment), $payload)->assertSessionHasNoErrors();
    $this->post(route('officer.event-payments.refund', $payment), [...$payload, 'amount_centavos' => 5000])->assertSessionHasErrors('refund');
    $this->post(route('officer.event-payments.refund', $payment), eventRefundPayload(15101))->assertSessionHasErrors('amount_centavos');
    $this->post(route('officer.event-payments.refund', $payment), eventRefundPayload(15100))->assertSessionHasNoErrors();
    expect(EventRefundRecord::count())->toBe(2)->and((int) EventRefundRecord::sum('amount_centavos'))->toBe(25100);
    expect($payment->fresh()->amount_centavos)->toBe(25100)->and($payment->fresh()->status)->toBe('confirmed');
    expect(EventRefundRecord::query()->first()->recorded_by_user_id)->toBe($reviewer->id);
    Mail::assertQueued(EventRegistrationUpdated::class, fn ($mail) => $mail->headline === 'An Event refund was recorded' && $mail->afterCommit);
});

test('Event payment and refund operations never change Property financial records or allocations', function () {
    $property = Property::factory()->withOpeningBalance('1000.00')->create(['prepaid_balance' => '75.00']);
    $charge = Charge::factory()->create(['property_id' => $property->id]);
    ChargeLine::factory()->create(['charge_id' => $charge->id, 'amount' => '1000.00']);
    $propertyPayment = Payment::factory()->create(['property_id' => $property->id, 'amount' => '1200.00']);
    app(ConfirmPayment::class)->handle($propertyPayment, User::factory()->superAdmin()->create());
    $balances = app(PropertyBalances::class)->forProperty($property->fresh());
    expect($balances)->toBe(['outstanding_balance' => '800.00', 'remaining_opening_balance' => '0.00', 'prepaid_balance' => '75.00']);
    expect(PaymentAllocation::count())->toBe(2);
    $tables = ['properties', 'charges', 'charge_lines', 'payments', 'payment_allocations'];
    $before = array_map(fn ($table) => DB::table($table)->get()->toJson(), $tables);
    $registration = reservePaidEvent();
    $payment = submittedEventPayment($registration);
    $reviewer = User::factory()->superAdmin()->create();
    $this->actingAs($reviewer)->post(route('officer.event-payments.review', $payment), ['decision' => 'confirmed']);
    $this->actingAs($registration->user)->post(route('event-registrations.request-cancellation', $registration), ['submission_key' => Str::uuid()->toString(), 'reason' => 'Cancel']);
    $this->actingAs($reviewer)->post(route('officer.event-cancellations.resolve', $registration->cancellationRequests()->sole()), ['decision' => 'approved', 'reason' => 'Approved']);
    $this->post(route('officer.event-payments.refund', $payment), eventRefundPayload())->assertSessionHasNoErrors();
    expect(array_map(fn ($table) => DB::table($table)->get()->toJson(), $tables))->toBe($before);
    expect(app(PropertyBalances::class)->forProperty($property->fresh()))->toBe($balances);
});

test('cancellation before review still permits acknowledging received money and refunding without issuing tickets', function () {
    $registration = reservePaidEvent();
    $payment = submittedEventPayment($registration);
    $reviewer = User::factory()->superAdmin()->create();
    $this->actingAs($registration->user)->post(route('event-registrations.request-cancellation', $registration), ['submission_key' => Str::uuid()->toString(), 'reason' => 'Cannot attend']);
    $this->actingAs($reviewer)->post(route('officer.event-cancellations.resolve', $registration->cancellationRequests()->sole()), ['decision' => 'approved', 'reason' => 'Approved']);
    $this->post(route('officer.event-payments.review', $payment), ['decision' => 'confirmed'])->assertSessionHasNoErrors();
    expect($payment->fresh()->status)->toBe('confirmed');
    expect($registration->fresh()->status)->toBe(EventRegistrationStatus::Cancelled);
    expect($registration->attendees()->whereNotNull('ticket_token')->exists())->toBeFalse();
    $this->post(route('officer.event-payments.refund', $payment), eventRefundPayload())->assertSessionHasNoErrors();
    Mail::assertNotQueued(EventRegistrationConfirmed::class);
});

test('attendee names can be corrected while payment is pending or awaiting review but never after expiry', function () {
    $registration = reservePaidEvent();
    $attendee = $registration->attendees()->firstOrFail();
    $this->actingAs($registration->user)->put(route('event-attendees.update', $attendee), ['name' => 'Correct name'])->assertSessionHasNoErrors();
    expect($attendee->fresh()->name)->toBe('Correct name')->and($attendee->fresh()->ticket_token)->toBeNull();
    submittedEventPayment($registration);
    $this->put(route('event-attendees.update', $attendee), ['name' => 'Another correction'])->assertSessionHasNoErrors();
    $unpaid = reservePaidEvent();
    $this->travel(24)->hours();
    $this->actingAs($unpaid->user)->put(route('event-attendees.update', $unpaid->attendees()->firstOrFail()), ['name' => 'Too late'])->assertSessionHasErrors('name');
});

test('cancellation and refunds enforce ownership review grants immutable request keys and valid actual refund details', function () {
    $registration = reservePaidEvent();
    $payment = submittedEventPayment($registration);
    $payload = ['submission_key' => Str::uuid()->toString(), 'reason' => 'Please cancel'];
    $this->actingAs(User::factory()->create())->post(route('event-registrations.request-cancellation', $registration), $payload)->assertNotFound();
    $this->actingAs($registration->user)->post(route('event-registrations.request-cancellation', $registration), $payload)->assertSessionHasNoErrors();
    $this->post(route('event-registrations.request-cancellation', $registration), [...$payload, 'reason' => 'Other'])->assertSessionHasErrors('reason');
    $this->post(route('event-registrations.request-cancellation', $registration), [...$payload, 'submission_key' => Str::uuid()->toString()])->assertSessionHasErrors('registration');
    $request = $registration->cancellationRequests()->sole();
    $this->post(route('officer.event-cancellations.resolve', $request), ['decision' => 'approved', 'reason' => 'Approved'])->assertForbidden();
    $this->post(route('officer.event-payments.refund', $payment), eventRefundPayload())->assertForbidden();
    $this->actingAs(User::factory()->superAdmin()->create());
    $this->post(route('officer.event-cancellations.resolve', $request), ['decision' => 'approved'])->assertSessionHasErrors('reason');
    $this->post(route('officer.event-cancellations.resolve', $request), ['decision' => 'approved', 'reason' => 'Approved'])->assertSessionHasNoErrors();
    $this->post(route('officer.event-cancellations.resolve', $request), ['decision' => 'declined', 'reason' => 'Other'])->assertSessionHasErrors('cancellation');
    foreach ([['amount_centavos' => 0], ['amount_centavos' => -1], ['method' => 'card'], ['reference' => ''], ['reason' => ''], ['refunded_at' => '2027-01-01T12:00']] as $invalid) {
        $this->post(route('officer.event-payments.refund', $payment), [...eventRefundPayload(), ...$invalid])->assertSessionHasErrors(array_key_first($invalid));
    }
});

test('capacity includes confirmed awaiting review and unexpired pending groups and guards decreases against that sum', function () {
    $pending = reservePaidEvent();
    $event = $pending->event;
    $event->update(['capacity' => 4]);
    EventRegistration::factory()->create(['event_id' => $event->id, 'attendee_count' => 1]);
    $awaiting = app(RegisterForEvent::class)->handle(User::factory()->create(), $event, ['submission_key' => Str::uuid()->toString(), 'attendees' => [['name' => 'Extra']]]);
    submittedEventPayment($awaiting);
    expect(app(EventRegistrationRules::class)->reservedPlaces($event))->toBe(4);
    expect(fn () => app(EventRegistrationRules::class)->ensureCanChange($event, ['capacity' => 3, 'price_centavos' => 12550, 'audience' => 'public']))->toThrow(ValidationException::class);
    $this->travel(24)->hours();
    expect(app(EventRegistrationRules::class)->reservedPlaces($event))->toBe(2);
    app(EventRegistrationRules::class)->ensureCanChange($event, ['capacity' => 2, 'price_centavos' => 12550, 'audience' => 'public']);
});
