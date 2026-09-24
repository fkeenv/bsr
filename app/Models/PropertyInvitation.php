<?php

namespace App\Models;

use App\Enums\MembershipRole;
use App\Enums\PropertyInvitationStatus;
use Database\Factories\PropertyInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $property_id
 * @property MembershipRole $role
 * @property int $created_by_user_id
 * @property string $token_hash
 * @property Carbon $expires_at
 * @property Carbon|null $consumed_at
 * @property Carbon|null $revoked_at
 * @property int|null $revoked_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Property $property
 * @property-read User $creator
 * @property-read User|null $revokedBy
 */
#[Fillable([
    'property_id',
    'role',
    'created_by_user_id',
    'token_hash',
    'expires_at',
    'consumed_at',
    'revoked_at',
    'revoked_by_user_id',
])]
#[Hidden(['token_hash'])]
class PropertyInvitation extends Model
{
    /** @use HasFactory<PropertyInvitationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => MembershipRole::class,
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_user_id');
    }

    public function status(): PropertyInvitationStatus
    {
        if ($this->revoked_at !== null) {
            return PropertyInvitationStatus::Revoked;
        }

        if ($this->consumed_at !== null) {
            return PropertyInvitationStatus::Consumed;
        }

        if ($this->expires_at->lessThanOrEqualTo(now())) {
            return PropertyInvitationStatus::Expired;
        }

        return PropertyInvitationStatus::Unused;
    }

    public function canBeRevoked(): bool
    {
        return $this->status() === PropertyInvitationStatus::Unused;
    }
}
