<?php

namespace App\Http\Requests;

use App\Models\Membership;
use Illuminate\Foundation\Http\FormRequest;

class EndMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $membership = $this->membership();

        if ($user === null || $membership === null) {
            return false;
        }

        return $user->id === $membership->user_id || $user->canAccessOfficerSurfaces();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();
        $membership = $this->membership();
        $isSelf = $user !== null && $membership !== null && $user->id === $membership->user_id;

        return [
            'end_reason' => $isSelf
                ? ['nullable', 'string', 'max:5000']
                : ['required', 'string', 'max:5000'],
        ];
    }

    private function membership(): ?Membership
    {
        $membership = $this->route('membership');

        return $membership instanceof Membership ? $membership : null;
    }
}
