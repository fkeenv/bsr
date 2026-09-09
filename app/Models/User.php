<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $mobile_number
 * @property bool $is_super_admin
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
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_super_admin' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_super_admin' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin;
    }

    /**
     * Whether this User Account may reach Officer-style surfaces.
     * Officer assignments (#21) will OR into this later.
     */
    public function canAccessOfficerSurfaces(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Whether this User Account may reach Administrator-style surfaces.
     * Administrator assignments (#21) will OR into this later.
     */
    public function canAccessAdministratorSurfaces(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Whether this User Account holds a live Membership.
     * Memberships (#20) will implement this later.
     */
    public function isMembershipHolder(): bool
    {
        return false;
    }

    /**
     * Plain User Accounts without a live Membership go to Membership Application onboarding.
     */
    public function mustCompleteMembershipOnboarding(): bool
    {
        return ! $this->isSuperAdmin() && ! $this->isMembershipHolder();
    }
}
