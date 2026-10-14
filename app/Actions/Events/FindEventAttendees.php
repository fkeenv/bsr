<?php

namespace App\Actions\Events;

use App\Data\EventAttendeeLookupData;
use App\Data\EventAttendeeLookupPageData;
use App\Enums\EventPermission;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\User;
use App\Support\EventRegistrationRules;
use App\Support\PlatformAccess;
use Illuminate\Database\Eloquent\Builder;

class FindEventAttendees
{
    public function __construct(private EventRegistrationRules $rules) {}

    public function handle(User $actor, Event $event, string $search = '', int $page = 1): EventAttendeeLookupPageData
    {
        abort_unless(PlatformAccess::allowsStaff($actor, EventPermission::CheckIn), 403);
        $attendees = EventAttendee::query()->whereHas('registration', fn ($query) => $query->where('event_id', $event->id))
            ->when(trim($search) !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereLike('name', '%'.trim($search).'%')
                        ->orWhereHas('registration.user', fn ($account) => $account->whereLike('name', '%'.trim($search).'%'));
                });
            })->with('registration.user')->orderBy('name')->orderBy('id')->paginate(50, page: $page);
        $data = $attendees->getCollection()->map(function (EventAttendee $attendee) use ($event): EventAttendeeLookupData {
            $attendee->registration->setRelation('event', $event);

            return EventAttendeeLookupData::fromModel($attendee, $this->rules);
        });

        return new EventAttendeeLookupPageData(array_values($data->all()), $attendees->currentPage(), $attendees->lastPage(), $attendees->total());
    }
}
