<?php

use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

test('simultaneous QR and manual admissions record one check in and preserve the first actor', function () {
    $this->travelTo(Carbon::parse('2026-10-14 00:00:00', 'UTC'));
    $databasePath = tempnam(sys_get_temp_dir(), 'bsr-check-in-race-');
    $environment = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $databasePath, 'DB_URL' => false,
        'CACHE_STORE' => 'array', 'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'BCRYPT_ROUNDS' => '4'];
    $originalConnection = DB::getDefaultConnection();
    $processes = [];
    try {
        (new Process([PHP_BINARY, 'artisan', 'migrate', '--force', '--no-interaction'], base_path(), $environment))->mustRun();
        config()->set('database.connections.check-in-race', [...config('database.connections.sqlite'), 'database' => $databasePath, 'url' => null]);
        DB::setDefaultConnection('check-in-race');
        $actors = [User::factory()->superAdmin()->create(), User::factory()->superAdmin()->create()];
        $event = Event::factory()->published()->create(['created_by_user_id' => $actors[0]->id]);
        $registration = EventRegistration::factory()->create(['event_id' => $event->id]);
        $attendee = EventAttendee::factory()->create(['event_registration_id' => $registration->id]);
        $streams = [];
        foreach ($actors as $index => $actor) {
            $streams[$index] = new InputStream;
            $processes[$index] = new Process([PHP_BINARY, base_path('tests/Fixtures/check-in-event-attendee.php'), (string) $actor->id, (string) $event->id, $attendee->ticket_token, $index === 0 ? 'qr' : 'manual'], base_path(), $environment);
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
        expect(array_column($results, 'outcome'))->toContain('checked-in', 'already-checked-in');
        expect($results[0]['checked_in_by_user_id'])->toBe($results[1]['checked_in_by_user_id']);
        expect($results[0]['checked_in_at'])->toBe('2026-10-14T00:00:00+00:00');
        expect($attendee->fresh()->checked_in_by_user_id)->toBe($results[0]['checked_in_by_user_id']);
        expect(EventAttendee::query()->whereNotNull('checked_in_at')->count())->toBe(1);
    } finally {
        foreach ($processes as $process) {
            if ($process->isRunning()) {
                $process->stop();
            }
        }
        DB::setDefaultConnection($originalConnection);
        DB::purge('check-in-race');
        @unlink($databasePath);
    }
});
