<?php

namespace App\Models;

use App\Enums\MembershipApplicationStatus;
use Database\Factories\MembershipApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $property_id
 * @property MembershipApplicationStatus $status
 * @property string|null $note
 * @property int $terms_of_service_version_id
 * @property int $privacy_policy_version_id
 * @property int|null $reviewed_by_user_id
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'property_id',
    'status',
    'note',
    'terms_of_service_version_id',
    'privacy_policy_version_id',
    'reviewed_by_user_id',
    'reviewed_at',
])]
class MembershipApplication extends Model
{
    /** @use HasFactory<MembershipApplicationFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MembershipApplicationStatus::class,
            'reviewed_at' => 'datetime',
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
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    /**
     * @return HasMany<HouseholdMember, $this>
     */
    public function householdMembers(): HasMany
    {
        return $this->hasMany(HouseholdMember::class);
    }

    /**
     * @return HasMany<EmergencyContact, $this>
     */
    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class);
    }

    /**
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * @return HasOne<Membership, $this>
     */
    public function membership(): HasOne
    {
        return $this->hasOne(Membership::class);
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * @param  Builder<MembershipApplication>  $query
     */
    #[Scope]
    protected function editable(Builder $query): void
    {
        $query->whereIn('status', [
            MembershipApplicationStatus::Pending->value,
            MembershipApplicationStatus::Rejected->value,
        ]);
    }
}
