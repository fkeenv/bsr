<?php

use App\Actions\Events\CancelEvent;
use App\Actions\Events\CheckInEventAttendee;
use App\Actions\Events\RegisterForEvent;
use App\Actions\Events\ReviewEventPayment;
use App\Models\Event;
use App\Models\EventPaymentRecord;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
Carbon::setTestNow('2026-10-16 00:00:00');
$actor = User::findOrFail((int) $argv[1]);
$event = Event::findOrFail((int) $argv[2]);
echo "READY\n";
flush();
fgets(STDIN);
try {
    match ($argv[3]) {
        'cancel' => $app->make(CancelEvent::class)->handle($actor, $event),
        'review' => $app->make(ReviewEventPayment::class)->handle($actor, EventPaymentRecord::findOrFail((int) $argv[4]), ['decision' => 'confirmed']),
        'register' => $app->make(RegisterForEvent::class)->handle($actor, $event, ['submission_key' => Str::uuid()->toString(), 'attendees' => [['name' => 'Concurrent attendee']]]),
        'check-in' => $app->make(CheckInEventAttendee::class)->handle($actor, $event, $argv[4]),
    };
    echo 'success';
} catch (ValidationException) {
    echo 'blocked';
}
