<?php

use App\Actions\Events\CheckInEventAttendee;
use App\Models\Event;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
Carbon::setTestNow('2026-10-14 00:00:00');
echo "READY\n";
flush();
fgets(STDIN);
$identity = $argv[4] === 'qr' ? route('event-tickets.show', ['attendee' => $argv[3]]) : $argv[3];
$result = $app->make(CheckInEventAttendee::class)->handle(User::findOrFail((int) $argv[1]), Event::findOrFail((int) $argv[2]), $identity);
echo json_encode($result->toArray(), JSON_THROW_ON_ERROR);
