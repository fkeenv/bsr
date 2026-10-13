<?php

namespace App\Http\Requests\Administrator;

use App\Enums\MembershipRole;
use App\Models\Membership;
use App\Models\User;
use App\Support\UserAccountMembershipAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserAccountMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $membership = $this->route('membership');
        $subject = $this->route('user');
        if (! $membership instanceof Membership || ! $subject instanceof User) {
            return false;
        }
        abort_unless($membership->user_id === $subject->id, 404);

        return UserAccountMembershipAccess::canManage($this->user(), $membership, $this->routeIs('administrator.user-accounts.memberships.end'));
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return ['confirm_staff_access_loss' => ['sometimes', 'boolean'], ...($this->routeIs('administrator.user-accounts.memberships.end')
            ? ['end_reason' => ['required', 'string', 'max:5000']] : ['role' => ['required', Rule::enum(MembershipRole::class)]])];
    }
}
