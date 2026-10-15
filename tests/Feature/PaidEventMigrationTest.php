<?php

use App\Enums\EventRegistrationStatus;
use App\Models\Event;
use App\Models\EventCancellationRequest;
use App\Models\EventPaymentRecord;
use App\Models\EventRefundRecord;
use App\Models\EventRegistration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function paidEventMigration(): Migration
{
    return require database_path('migrations/2026_10_14_054728_add_paid_event_registration_records.php');
}

test('paid Event migration emits MySQL index and constraint names within its 64 character limit', function () {
    $originalSchema = Schema::getFacadeRoot();
    $mysql = new MySqlConnection(fn () => throw new LogicException('SQL compilation must not connect to a database.'), 'migration_preview', '', ['version' => '8.4.0']);
    try {
        Schema::swap($mysql->getSchemaBuilder());
        $queries = [];
        DB::connection()->pretend(function () use ($mysql, &$queries): void {
            $queries = $mysql->pretend(fn () => paidEventMigration()->up());
        });
        $names = [];
        foreach ($queries as $query) {
            preg_match_all('/\b(?:unique|index|constraint)\s+`([^`]+)`/i', $query['query'], $matches);
            $names = [...$names, ...$matches[1]];
        }
        expect($names)->not->toBeEmpty();
        foreach ($names as $name) {
            expect(strlen($name))->toBeLessThanOrEqual(64, 'MySQL identifier: '.$name);
        }
    } finally {
        Schema::swap($originalSchema);
    }
});

test('the partially applied paid Event migration resumes without losing existing records and creates missing uniqueness', function () {
    Schema::drop('event_refund_records');
    $unique = collect(Schema::getIndexes('event_cancellation_requests'))->first(fn ($index) => $index['unique'] && $index['columns'] === ['event_registration_id', 'submission_key']);
    Schema::table('event_cancellation_requests', fn (Blueprint $table) => $table->dropUnique($unique['name']));
    $registration = EventRegistration::factory()->create(['event_id' => Event::factory()->published()->create()->id, 'price_centavos' => 12550, 'total_centavos' => 12550, 'status' => EventRegistrationStatus::Rejected, 'cancelled_at' => now()]);
    $payment = EventPaymentRecord::factory()->create(['event_registration_id' => $registration->id, 'status' => 'rejected', 'review_reason' => 'Preserve rejection history']);
    $cancellation = EventCancellationRequest::factory()->create(['event_registration_id' => $registration->id]);
    $before = [$registration->fresh()->getRawOriginal(), $payment->fresh()->getRawOriginal(), $cancellation->fresh()->getRawOriginal()];

    paidEventMigration()->up();

    expect([$registration->fresh()->getRawOriginal(), $payment->fresh()->getRawOriginal(), $cancellation->fresh()->getRawOriginal()])->toBe($before);
    expect(Schema::hasTable('event_refund_records'))->toBeTrue();
    expect(Schema::hasIndex('event_cancellation_requests', ['event_registration_id', 'submission_key'], 'unique'))->toBeTrue();
    expect(Schema::hasIndex('event_refund_records', ['event_payment_record_id', 'submission_key'], 'unique'))->toBeTrue();
    expect(fn () => EventCancellationRequest::factory()->create(['event_registration_id' => $registration->id, 'submission_key' => $cancellation->submission_key]))->toThrow(QueryException::class);
});

test('retrying an already complete paid Event migration preserves payment cancellation and refund history', function () {
    $registration = EventRegistration::factory()->create(['status' => EventRegistrationStatus::Expired, 'cancelled_at' => now(), 'expired_at' => now()]);
    $payment = EventPaymentRecord::factory()->create(['event_registration_id' => $registration->id, 'status' => 'confirmed']);
    $cancellation = EventCancellationRequest::factory()->create(['event_registration_id' => $registration->id, 'status' => 'approved']);
    $refund = EventRefundRecord::factory()->create(['event_payment_record_id' => $payment->id]);
    $before = [$registration->fresh()->getRawOriginal(), $payment->fresh()->getRawOriginal(), $cancellation->fresh()->getRawOriginal(), $refund->fresh()->getRawOriginal()];

    paidEventMigration()->up();
    paidEventMigration()->up();

    expect([$registration->fresh()->getRawOriginal(), $payment->fresh()->getRawOriginal(), $cancellation->fresh()->getRawOriginal(), $refund->fresh()->getRawOriginal()])->toBe($before);
});

test('a fresh paid schema adds its fields and backfills only legacy cancelled registrations', function () {
    $registration = EventRegistration::factory()->create(['cancelled_at' => now()]);
    $confirmedAt = $registration->confirmed_at;
    $cancelledAt = $registration->cancelled_at;
    paidEventMigration()->down();

    paidEventMigration()->up();

    expect($registration->fresh()->status)->toBe(EventRegistrationStatus::Cancelled);
    expect($registration->fresh()->confirmed_at->equalTo($confirmedAt))->toBeTrue();
    expect($registration->fresh()->cancelled_at->equalTo($cancelledAt))->toBeTrue();
    expect(Schema::hasIndex('event_registrations', ['expires_at']))->toBeTrue();
    expect(Schema::hasIndex('event_cancellation_requests', ['event_registration_id', 'submission_key'], 'unique'))->toBeTrue();
});
