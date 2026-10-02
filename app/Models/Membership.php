<?php

namespace App\Models;

use App\Enums\MembershipRole;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $property_id
 * @property int|null $property_invitation_id
 * @property int|null $terms_of_service_version_id
 * @property int|null $privacy_policy_version_id
 * @property MembershipRole $role
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property int|null $ended_by_user_id
 * @property string|null $end_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'property_id',
    'property_invitation_id',
    'terms_of_service_version_id',
    'privacy_policy_version_id',
    'role',
    'started_at',
    'ended_at',
    'ended_by_user_id',
    'end_reason',
])]
class Membership extends Model
{
    /** @use HasFactory<MembershipFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => MembershipRole::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return BelongsTo<PropertyInvitation, $this>
     */
    public function propertyInvitation(): BelongsTo
    {
        return $this->belongsTo(PropertyInvitation::class);
    }

    /**
     * @return BelongsTo<LegalDocumentVersion, $this>
     */
    public function termsOfServiceVersion(): BelongsTo
    {
        return $this->belongsTo(LegalDocumentVersion::class, 'terms_of_service_version_id');
    }

    /**
     * @return BelongsTo<LegalDocumentVersion, $this>
     */
    public function privacyPolicyVersion(): BelongsTo
    {
        return $this->belongsTo(LegalDocumentVersion::class, 'privacy_policy_version_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by_user_id');
    }

    public function isLive(): bool
    {
        return $this->ended_at === null;
    }

    /**
     * @param  Builder<Membership>  $query
     */
    #[Scope]
    protected function live(Builder $query): void
    {
        $query->whereNull('ended_at');
    }
}
