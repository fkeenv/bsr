<?php

namespace App\Http\Controllers;

use App\Actions\Events\CorrectEventAttendeeName;
use App\Http\Requests\CorrectEventAttendeeNameRequest;
use App\Models\EventAttendee;
use Illuminate\Http\RedirectResponse;

class EventAttendeeController
{
    public function update(CorrectEventAttendeeNameRequest $request, EventAttendee $attendee, CorrectEventAttendeeName $correct): RedirectResponse
    {
        $correct->handle($request->user(), $attendee, $request->validated('name'));

        return back()->with('success', 'Attendee name corrected. The same ticket remains valid.');
    }
}
