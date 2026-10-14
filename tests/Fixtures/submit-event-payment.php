<?php

use App\Actions\Events\ExpireEventReservation;
use App\Actions\Events\SubmitEventPayment;
use App\Mail\EventRegistrationUpdated;
use App\Models\EventRegistration;
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
$registration = EventRegistration::findOrFail((int) $argv[1]);
try {
    if ($argv[2] === 'expire') {
        $app->make(ExpireEventReservation::class)->handle($registration);
    } else {
        $app->make(SubmitEventPayment::class)->handle($registration->user, $registration, ['method' => 'cash', 'reference' => 'Office receipt 123']);
    }
    echo json_encode(['accepted' => true, 'mail_count' => Mail::queued(EventRegistrationUpdated::class)->count()], JSON_THROW_ON_ERROR);
} catch (ValidationException $exception) {
    echo json_encode(['accepted' => false, 'mail_count' => Mail::queued(EventRegistrationUpdated::class)->count()], JSON_THROW_ON_ERROR);
}
