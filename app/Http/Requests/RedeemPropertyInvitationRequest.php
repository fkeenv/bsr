<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RedeemPropertyInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accept_terms' => ['accepted'],
            'accept_privacy' => ['accepted'],
            'terms_of_service_version_id' => ['required', 'integer', 'exists:legal_document_versions,id'],
            'privacy_policy_version_id' => ['required', 'integer', 'exists:legal_document_versions,id'],
        ];
    }
}
