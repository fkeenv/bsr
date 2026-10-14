<?php

namespace App\Http\Requests;

use App\Support\EventRegistrationRules;
use Illuminate\Foundation\Http\FormRequest;

class CorrectEventAttendeeNameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['name' => EventRegistrationRules::nameRules(), 'attendees' => ['prohibited'], 'attendee_count' => ['prohibited'], 'checked_in_at' => ['prohibited'], 'ticket_token' => ['prohibited']];
    }
}
