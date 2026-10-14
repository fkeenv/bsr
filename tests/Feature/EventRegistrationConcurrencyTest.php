<?php

use App\Enums\EventRegistrationStatus;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\EventPaymentRecord;
use App\Models\EventRegistration;
use App\Models\User;
use App\Support\EventRegistrationRules;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

beforeEach(fn () => $this->travelTo(Carbon::parse('2026-10-14 00:00:00', 'UTC')));

test('simultaneous group submissions cannot oversell or duplicate a registration', function (bool $duplicate) {
    $databasePath = tempnam(sys_get_temp_dir(), 'bsr-event-race-');
    $environment = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $databasePath, 'DB_URL' => false,
        'CACHE_STORE' => 'array', 'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'BCRYPT_ROUNDS' => '4'];
    $originalConnection = DB::getDefaultConnection();
    $processes = [];
    try {
        (new Process([PHP_BINARY, 'artisan', 'migrate', '--force', '--no-interaction'], base_path(), $environment))->mustRun();
        config()->set('database.connections.event-race', [...config('database.connections.sqlite'), 'database' => $databasePath, 'url' => null]);
        DB::setDefaultConnection('event-race');
        $firstAccount = User::factory()->create();
        $secondAccount = $duplicate ? $firstAccount : User::factory()->create();
        $event = Event::factory()->published()->create(['capacity' => 4, 'created_by_user_id' => $firstAccount->id]);
        $firstKey = Str::uuid()->toString();
        $streams = [];
        foreach ([$firstAccount, $secondAccount] as $index => $account) {
            $streams[$index] = new InputStream;
            $key = $duplicate || $index === 0 ? $firstKey : Str::uuid()->toString();
            $processes[$index] = new Process([PHP_BINARY, base_path('tests/Fixtures/reserve-event-group.php'), (string) $account->id, (string) $event->id, $key], base_path(), $environment);
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
            $process->isRunning();
        }
        $results = [];
        foreach ($processes as $process) {
            $process->wait();
            expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());
            $results[] = json_decode(substr($process->getOutput(), strpos($process->getOutput(), "\n") + 1), true, flags: JSON_THROW_ON_ERROR);
        }
        expect(EventRegistration::count())->toBe(1);
        expect(EventAttendee::count())->toBe(3);
        expect(EventRegistration::sum('attendee_count'))->toBe(3);
        expect(array_sum(array_column($results, 'mail_count')))->toBe(1);
        expect(count(array_filter($results, fn ($result) => $result['accepted'])))->toBe($duplicate ? 2 : 1);
    } finally {
        foreach ($processes as $process) {
            if ($process->isRunning()) {
                $process->stop();
            }
        }
        DB::setDefaultConnection($originalConnection);
        DB::purge('event-race');
        @unlink($databasePath);
    }
})->with(['different submissions' => false, 'same submission' => true]);

test('payment submission and expiry serialize without losing review holds or creating duplicate payments', function (bool $expired, bool $duplicate) {
    $databasePath = tempnam(sys_get_temp_dir(), 'bsr-event-race-');
    $environment = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $databasePath, 'DB_URL' => false,
        'CACHE_STORE' => 'array', 'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'BCRYPT_ROUNDS' => '4'];
    $originalConnection = DB::getDefaultConnection();
    $processes = [];
    try {
        (new Process([PHP_BINARY, 'artisan', 'migrate', '--force', '--no-interaction'], base_path(), $environment))->mustRun();
        config()->set('database.connections.event-race', [...config('database.connections.sqlite'), 'database' => $databasePath, 'url' => null]);
        DB::setDefaultConnection('event-race');
        $account = User::factory()->create();
        $event = Event::factory()->published()->create(['capacity' => 1, 'price_centavos' => 10000, 'payment_instructions' => 'Pay at the office.', 'created_by_user_id' => $account->id]);
        $registration = EventRegistration::factory()->create([
            'event_id' => $event->id, 'user_id' => $account->id, 'status' => EventRegistrationStatus::PendingPayment,
            'price_centavos' => 10000, 'total_centavos' => 10000, 'confirmed_at' => null,
            'expires_at' => $expired ? now() : now()->addSecond(),
        ]);
        $streams = [];
        foreach (['submit', $duplicate ? 'submit' : 'expire'] as $index => $operation) {
            $streams[$index] = new InputStream;
            $processes[$index] = new Process([PHP_BINARY, base_path('tests/Fixtures/submit-event-payment.php'), (string) $registration->id, $operation], base_path(), $environment);
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
            $process->isRunning();
        }
        $results = [];
        foreach ($processes as $process) {
            $process->wait();
            expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());
            $results[] = json_decode(substr($process->getOutput(), strpos($process->getOutput(), "\n") + 1), true, flags: JSON_THROW_ON_ERROR);
        }
        expect(EventPaymentRecord::count())->toBe($expired ? 0 : 1);
        expect($registration->fresh()->status)->toBe($expired ? EventRegistrationStatus::Expired : EventRegistrationStatus::AwaitingReview);
        expect(app(EventRegistrationRules::class)->reservedPlaces($event))->toBe($expired ? 0 : 1);
        expect(array_sum(array_column($results, 'mail_count')))->toBe($expired ? 1 : 0);
    } finally {
        foreach ($processes as $process) {
            if ($process->isRunning()) {
                $process->stop();
            }
        }
        DB::setDefaultConnection($originalConnection);
        DB::purge('event-race');
        @unlink($databasePath);
    }
})->with(['expired race' => [true, false], 'submitted race' => [false, false], 'duplicate submissions' => [false, true]]);
