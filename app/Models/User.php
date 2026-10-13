<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\MembershipRole;
use App\Enums\PlatformRole;
use App\Support\PlatformAccess;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $mobile_number
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'mobile_number', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return PlatformAccess::canAccessSurface($this, 'super-admin');
    }

    public function canAccessOfficerSurfaces(): bool
    {
        return PlatformAccess::canAccessSurface($this, 'officer');
    }

    public function canAccessAdministratorSurfaces(): bool
    {
        return PlatformAccess::canAccessSurface($this, 'administrator');
    }

    /**
     * @return HasOne<UserProfile, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function hasLiveMembership(): bool
    {
        return $this->memberships()->live()->exists();
    }

    public function hasLiveOwnerMembership(): bool
    {
        return $this->memberships()
            ->live()
            ->where('role', MembershipRole::Owner)
            ->exists();
    }

    public function isMembershipHolder(): bool
    {
        return $this->hasLiveMembership();
    }

    public function assignPlatformRole(PlatformRole $role): void
    {
        $roles = [$role->value, PlatformRole::User->value];

        if ($role === PlatformRole::User) {
            $roles = [PlatformRole::User->value];
        }

        $this->assignRole($roles);
    }
}
