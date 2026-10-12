<?php

namespace App\Http\Requests;

use App\Models\Membership;
use App\Support\MembershipAccess;
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

        return MembershipAccess::canEnd($user, $membership);
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
