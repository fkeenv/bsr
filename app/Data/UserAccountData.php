<?php

namespace App\Data;

use App\Enums\MembershipRole;
use App\Enums\PlatformRole;
use App\Models\Membership;
use App\Models\User;
use Spatie\LaravelData\Data;

class UserAccountData extends Data
{
    /**
     * @param  list<string>  $staff_roles
     * @param  list<UserAccountMembershipData>  $live_memberships
     */
    public function __construct(public int $id, public string $name, public string $email, public ?string $mobile_number, public ?string $registered_at, public array $staff_roles, public array $live_memberships, public bool $is_super_admin, public bool $has_owner_membership) {}

    public static function fromModel(User $user): self
    {
        $live = $user->memberships->filter(fn (Membership $membership): bool => $membership->isLive());

        return new self($user->id, $user->name, $user->email, $user->mobile_number, $user->created_at?->toIso8601String(),
            array_values($user->roles->filter(fn ($role): bool => $role->getAttribute('guard_name') === 'web' && ! in_array($role->getAttribute('name'), [PlatformRole::SuperAdmin->value, PlatformRole::Member->value, PlatformRole::User->value], true))->pluck('name')->sort()->all()),
            array_values($live->map(fn (Membership $membership): UserAccountMembershipData => UserAccountMembershipData::fromModel($membership))->all()),
            $user->roles->contains(fn ($role): bool => $role->getAttribute('guard_name') === 'web' && $role->getAttribute('name') === PlatformRole::SuperAdmin->value), $live->contains('role', MembershipRole::Owner));
    }
}
