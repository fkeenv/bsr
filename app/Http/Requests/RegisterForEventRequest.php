<?php

namespace App\Http\Requests;

use App\Support\EventRegistrationRules;
use Illuminate\Foundation\Http\FormRequest;

class RegisterForEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return EventRegistrationRules::submissionRules();
    }
}
