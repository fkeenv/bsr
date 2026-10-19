<?php

use App\Models\Event;
use App\Models\EventCancellationRequest;
use App\Models\EventPaymentRecord;
use App\Models\EventRefundRecord;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(Carbon::parse('2026-10-18 00:00:00', 'UTC'));
});

test('finance search and Event filters preserve work matching and exclude free registrations', function (string $search) {
    $account = User::factory()->create(['name' => 'Maria Santos', 'email' => 'maria-neighbor@example.com']);
    $matching = financeQueueRegistration(['id' => 718, 'user_id' => $account->id, 'status' => 'awaiting-review']);
    $matching->event->update(['title' => 'Seniors afternoon']);
    EventPaymentRecord::factory()->create(['event_registration_id' => $matching->id, 'reference' => 'GCash-neighbor-123']);
    financeQueueRegistration(['user_id' => $account->id, 'price_centavos' => 0]);
    financeQueueRegistration();

    $this->actingAs(User::factory()->superAdmin()->create())->get(route('officer.event-finances.index', ['search' => $search, 'event' => $matching->event_id]))
        ->assertInertia(fn (Assert $page) => $page->has('registrations', 1)
            ->where('registrations.0.registration.id', $matching->id)
            ->where('table.values.search', $search)->where('table.values.event', (string) $matching->event_id)
            ->where('workOptions.0.count', 1));
})->with(['Maria', 'maria-neighbor@example.com', 'Seniors afternoon', 'GCash-neighbor-123', '718']);

test('financial work and counts respect independent reviewer permissions', function (string $permission, string $work, string $forbidden) {
    $pending = financeQueueRegistration(['status' => 'awaiting-review']);
    EventPaymentRecord::factory()->create(['event_registration_id' => $pending->id]);
    $refundable = financeQueueRegistration(['status' => 'cancelled', 'cancelled_at' => now()]);
    EventPaymentRecord::factory()->create(['event_registration_id' => $refundable->id, 'status' => 'confirmed']);
    $officer = User::factory()->officer()->create();
    Role::findByName('officer')->syncPermissions([$permission]);

    $this->actingAs($officer)->get(route('officer.event-finances.index'))->assertInertia(fn (Assert $page) => $page
        ->has('registrations', 1)->where('registrations.0.work', [$work])->where('workOptions.0.count', 1));
    $this->get(route('officer.event-finances.index', ['work' => $forbidden]))->assertForbidden();
})->with([
    ['events.payments.review', 'payment-review', 'refund-due'],
    ['events.refunds.review', 'refund-due', 'payment-review'],
]);

test('financial history is paginated deterministically and an emptied final page returns to available results', function () {
    $account = User::factory()->create();
    $event = Event::factory()->published()->create();
    $registrations = EventRegistration::factory()->count(26)->create(['user_id' => $account->id, 'event_id' => $event->id, 'price_centavos' => 10000, 'total_centavos' => 10000]);
    $this->actingAs(User::factory()->superAdmin()->create());

    $this->get(route('officer.event-finances.index', ['work' => 'all']))->assertInertia(fn (Assert $page) => $page
        ->has('registrations', 25)->where('pagination.total', 26)->where('pagination.last_page', 2)
        ->where('registrations.0.registration.id', $registrations->last()->id));
    $this->get(route('officer.event-finances.index', ['work' => 'all', 'page' => 2]))->assertInertia(fn (Assert $page) => $page
        ->has('registrations', 1)->where('pagination.current_page', 2)->where('registrations.0.registration.id', $registrations->first()->id));
    $this->get(route('officer.event-finances.index', ['work' => 'all', 'page' => 999]))->assertInertia(fn (Assert $page) => $page
        ->has('registrations', 1)->where('pagination.current_page', 2));
});

test('resolved financial work leaves the queue but remains in history', function () {
    $registration = financeQueueRegistration(['status' => 'cancelled', 'cancelled_at' => now()]);
    $payment = EventPaymentRecord::factory()->create(['event_registration_id' => $registration->id, 'status' => 'confirmed']);
    $this->actingAs(User::factory()->superAdmin()->create());
    $this->get(route('officer.event-finances.index', ['work' => 'refund-due']))->assertInertia(fn (Assert $page) => $page->has('registrations', 1));

    EventRefundRecord::factory()->create(['event_payment_record_id' => $payment->id]);

    $this->get(route('officer.event-finances.index', ['work' => 'refund-due', 'page' => 2]))->assertInertia(fn (Assert $page) => $page
        ->has('registrations', 0)->where('pagination.current_page', 1)->where('workOptions.0.count', 0));
    $this->get(route('officer.event-finances.index', ['work' => 'all']))->assertInertia(fn (Assert $page) => $page
        ->has('registrations', 1)->where('registrations.0.work', [])
        ->where('registrations.0.financial.payment.refund_due_centavos', 0)
        ->where('registrations.0.financial.payment.refunded_centavos', 10000));
});

test('invalid financial queue filters return validation errors', function (string $key, mixed $value) {
    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('officer.event-finances.index', [$key => $value]))->assertSessionHasErrors($key);
})->with([['work', 'not-a-view'], ['page', 0], ['event', -1], ['search', ['invalid']]]);

function financeQueueRegistration(array $attributes = []): EventRegistration
{
    return EventRegistration::factory()->create([
        'event_id' => Event::factory()->published()->create(['price_centavos' => 10000, 'payment_instructions' => 'Pay at the office.'])->id,
        'price_centavos' => 10000, 'total_centavos' => 10000, ...$attributes,
    ]);
}

test('the finance queue surfaces overlapping work and distinguishes money still due from recorded refunds', function () {
    $pending = financeQueueRegistration(['status' => 'awaiting-review']);
    EventPaymentRecord::factory()->create(['event_registration_id' => $pending->id]);
    EventCancellationRequest::factory()->create(['event_registration_id' => $pending->id]);
    $refund = financeQueueRegistration(['status' => 'cancelled', 'cancelled_at' => now()]);
    $payment = EventPaymentRecord::factory()->create(['event_registration_id' => $refund->id, 'status' => 'confirmed']);
    EventRefundRecord::factory()->create(['event_payment_record_id' => $payment->id, 'amount_centavos' => 4000]);
    $finished = financeQueueRegistration(['status' => 'cancelled', 'cancelled_at' => now()]);
    $finishedPayment = EventPaymentRecord::factory()->create(['event_registration_id' => $finished->id, 'status' => 'confirmed']);
    EventRefundRecord::factory()->create(['event_payment_record_id' => $finishedPayment->id]);
    financeQueueRegistration(['price_centavos' => 0]);

    $this->actingAs(User::factory()->superAdmin()->create())->get(route('officer.event-finances.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('registrations', 2)->where('pagination.total', 2)
            ->where('workOptions.0.value', 'attention')->where('workOptions.0.count', 2)
            ->where('registrations.0.registration.id', $refund->id)
            ->where('registrations.0.work', ['refund-due'])
            ->where('registrations.0.financial.payment.refund_due_centavos', 6000)
            ->where('registrations.0.financial.payment.refunded_centavos', 4000)
            ->where('registrations.1.registration.id', $pending->id)
            ->where('registrations.1.work', ['payment-review', 'cancellation-review']));
});
