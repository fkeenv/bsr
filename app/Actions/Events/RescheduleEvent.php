<?php

namespace App\Actions\Events;

use App\Enums\EventPermission;
use App\Enums\EventStatus;
use App\Mail\EventRegistrationUpdated;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Support\EventDetails;
use App\Support\PlatformAccess;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RescheduleEvent
{
    /** @param array<string, mixed> $data */
    public function handle(User $actor, Event $event, array $data): void
    {
        abort_unless(PlatformAccess::allowsStaff($actor, EventPermission::Manage), 403);
        $data = Validator::make($data, Arr::only(EventDetails::rules(), ['starts_at', 'ends_at', 'registration_deadline']))->validate();
        foreach ($data as $field => $value) {
            $data[$field] = Carbon::createFromFormat('!Y-m-d\TH:i', $value, 'Asia/Manila')->utc();
        }
        foreach (['starts_at', 'registration_deadline'] as $field) {
            if (! $data[$field]->isFuture()) {
                throw ValidationException::withMessages([$field => 'Choose a future date and time in Manila time.']);
            }
        }
        DB::transaction(function () use ($actor, $event, $data): void {
            $event = Event::query()->lockForUpdate()->findOrFail($event->id);
            if (! $event->status->isPublished()) {
                throw ValidationException::withMessages(['event' => 'Only published Events can be rescheduled.']);
            }
            if ($event->starts_at->equalTo($data['starts_at']) && $event->ends_at->equalTo($data['ends_at'])) {
                throw ValidationException::withMessages(['starts_at' => 'Choose a different Event schedule.']);
            }
            $event->scheduleChanges()->create([
                'changed_by_user_id' => $actor->id,
                'old_starts_at' => $event->starts_at, 'old_ends_at' => $event->ends_at, 'old_registration_deadline' => $event->registration_deadline,
                'new_starts_at' => $data['starts_at'], 'new_ends_at' => $data['ends_at'], 'new_registration_deadline' => $data['registration_deadline'],
            ]);
            $previous = $event->starts_at->copy()->timezone('Asia/Manila')->format('F j, Y g:i A');
            $event->update([...$data, 'status' => EventStatus::Rescheduled, 'updated_by_user_id' => $actor->id]);
            $schedule = $event->starts_at->copy()->timezone('Asia/Manila')->format('F j, Y g:i A').' to '.$event->ends_at->copy()->timezone('Asia/Manila')->format('F j, Y g:i A');
            $deadline = $event->registration_deadline->copy()->timezone('Asia/Manila')->format('F j, Y g:i A');
            $message = "The Event has moved from {$previous} to {$schedule} (Manila time). The new registration deadline is {$deadline} (Manila time). Existing tickets remain the same. Unpaid reservations keep their original expiry. Confirmed paid registrations may request whole-registration cancellation and a full refund; staff record money returned separately.";
            $event->registrations()->with('user')->eachById(function (EventRegistration $registration) use ($message): void {
                Mail::to($registration->user)->queue((new EventRegistrationUpdated($registration, 'Your Event was rescheduled', $message))->afterCommit());
            }, 100);
        }, 5);
    }
}
