<?php

use App\Actions\Events\RegisterForEvent;
use App\Mail\EventRegistrationConfirmed;
use App\Models\Event;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
Carbon::setTestNow('2026-10-14 00:00:00');
Mail::fake();

echo "READY\n";
flush();
fgets(STDIN);

try {
    $registration = $app->make(RegisterForEvent::class)->handle(
        User::findOrFail((int) $argv[1]), Event::findOrFail((int) $argv[2]),
        ['submission_key' => $argv[3], 'attendees' => [['name' => 'One'], ['name' => 'Two'], ['name' => 'Three']]],
    );
    echo json_encode(['accepted' => true, 'registration_id' => $registration->id, 'mail_count' => Mail::queued(EventRegistrationConfirmed::class)->count()], JSON_THROW_ON_ERROR);
} catch (ValidationException $exception) {
    echo json_encode(['accepted' => false, 'errors' => $exception->errors(), 'mail_count' => Mail::queued(EventRegistrationConfirmed::class)->count()], JSON_THROW_ON_ERROR);
}
