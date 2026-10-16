<?php

use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\EventPaymentRecord;
use App\Models\EventRegistration;
use App\Models\User;
use App\Support\EventRegistrationRules;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

test('Event cancellation races preserve history and cannot leave valid tickets or active reservations', function (string $operation) {
    $this->travelTo(Carbon::parse('2026-10-16 00:00:00', 'UTC'));
    $databasePath = tempnam(sys_get_temp_dir(), 'bsr-cancellation-race-');
    $environment = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $databasePath, 'DB_URL' => false,
        'CACHE_STORE' => 'array', 'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'BCRYPT_ROUNDS' => '4'];
    $originalConnection = DB::getDefaultConnection();
    $processes = [];
    try {
        (new Process([PHP_BINARY, 'artisan', 'migrate', '--force', '--no-interaction'], base_path(), $environment))->mustRun();
        config()->set('database.connections.cancellation-race', [...config('database.connections.sqlite'), 'database' => $databasePath, 'url' => null]);
        DB::setDefaultConnection('cancellation-race');
        $actor = User::factory()->superAdmin()->create();
        $event = Event::factory()->published()->create(['created_by_user_id' => $actor->id, 'price_centavos' => $operation === 'review' ? 10000 : 0]);
        $registration = EventRegistration::factory()->create(['event_id' => $event->id, 'status' => $operation === 'review' ? 'awaiting-review' : 'confirmed', 'price_centavos' => $event->price_centavos, 'total_centavos' => $event->price_centavos]);
        $attendee = EventAttendee::factory()->create(['event_registration_id' => $registration->id, ...($operation === 'review' ? ['ticket_token' => null] : [])]);
        $argument = $attendee->ticket_token ?? '';
        if ($operation === 'review') {
            $argument = (string) EventPaymentRecord::factory()->create(['event_registration_id' => $registration->id])->id;
        }
        $streams = [];
        foreach (['cancel', $operation] as $index => $mode) {
            $streams[$index] = new InputStream;
            $processes[$index] = new Process([PHP_BINARY, base_path('tests/Fixtures/cancel-event-race.php'), (string) $actor->id, (string) $event->id, $mode, $argument], base_path(), $environment);
            $processes[$index]->setInput($streams[$index])->setTimeout(20)->start();
        }
        foreach ($processes as $process) {
            $ready = false;
            foreach ($process->getIterator(Process::ITER_KEEP_OUTPUT) as $output) {
                if (str_contains($output, 'READY')) {
                    $ready = true;
                    break;
                }
            }
            expect($ready)->toBeTrue();
        }
        foreach ($streams as $stream) {
            $stream->write("GO\n");
            $stream->close();
        }
        foreach ($processes as $process) {
            $process->wait();
            expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());
        }
        expect($event->fresh()->status->value)->toBe('cancelled');
        expect(EventRegistration::query()->whereNull('cancelled_at')->count())->toBe(0);
        foreach (EventAttendee::query()->with('registration.event')->get() as $ticket) {
            expect(app(EventRegistrationRules::class)->ticketIsValid($ticket))->toBeFalse();
        }
        expect(EventAttendee::find($attendee->id))->not->toBeNull();
        if ($operation === 'review') {
            expect(EventPaymentRecord::query()->sole()->status)->toBe('confirmed');
        }
        if ($operation === 'check-in') {
            expect($attendee->fresh()->checked_in_at !== null)->toBe(str_contains($processes[1]->getOutput(), 'success'));
        }
    } finally {
        foreach ($processes as $process) {
            if ($process->isRunning()) {
                $process->stop();
            }
        }
        DB::setDefaultConnection($originalConnection);
        DB::purge('cancellation-race');
        @unlink($databasePath);
    }
})->with(['register', 'review', 'check-in']);
