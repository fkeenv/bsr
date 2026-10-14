<?php

namespace App\Actions\Events;

use App\Enums\EventPermission;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use App\Support\EventDetails;
use App\Support\EventRegistrationRules;
use App\Support\PlatformAccess;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class SaveEvent
{
    public function __construct(private EventRegistrationRules $registrations) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data, ?Event $event = null): Event
    {
        abort_unless(PlatformAccess::allowsStaff($actor, EventPermission::Manage), 403);
        $data = EventDetails::validated($data);
        $image = $data['image'] ?? null;
        $removeImage = $data['remove_image'] ?? false;
        unset($data['image'], $data['remove_image']);
        $newImagePath = null;
        try {
            return DB::transaction(function () use ($actor, $data, $event, $image, $removeImage, &$newImagePath): Event {
                $saved = $event !== null ? Event::query()->lockForUpdate()->findOrFail($event->id) : new Event;
                if ($saved->exists) {
                    if ($saved->status === EventStatus::Cancelled) {
                        throw ValidationException::withMessages(['event' => 'Cancelled Events cannot be edited.']);
                    }
                    $this->registrations->ensureCanChange($saved, $data);
                    if ($saved->status === EventStatus::Published &&
                        (! $saved->starts_at->equalTo($data['starts_at']) || ! $saved->ends_at->equalTo($data['ends_at']))) {
                        throw ValidationException::withMessages(['starts_at' => 'Published schedules are locked. Rescheduling will be available separately.']);
                    }
                } else {
                    $saved->status = EventStatus::Draft;
                    $saved->created_by_user_id = $actor->id;
                }
                if ($image instanceof UploadedFile) {
                    $newImagePath = $image->store('event-images', 'local');
                    if ($newImagePath === false) {
                        throw ValidationException::withMessages(['image' => 'The Event image could not be stored.']);
                    }
                    $saved->image_path = $newImagePath;
                } elseif ($removeImage) {
                    $saved->image_path = null;
                }
                $saved->fill($data);
                $saved->updated_by_user_id = $actor->id;
                $saved->save();

                return $saved;
            });
        } catch (Throwable $exception) {
            if (is_string($newImagePath)) {
                Storage::disk('local')->delete($newImagePath);
            }
            throw $exception;
        }
    }
}
