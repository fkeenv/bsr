<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $property_id
 * @property int|null $declared_by_user_id
 * @property string $amount
 * @property string $prepaid_amount
 * @property PaymentMethod $method
 * @property string|null $reference
 * @property string|null $screenshot_path
 * @property PaymentStatus $status
 * @property string|null $rejection_reason
 * @property string|null $void_reason
 * @property int|null $confirmed_by_user_id
 * @property Carbon|null $confirmed_at
 * @property int|null $rejected_by_user_id
 * @property Carbon|null $rejected_at
 * @property int|null $voided_by_user_id
 * @property Carbon|null $voided_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Property $property
 * @property-read User|null $declaredBy
 * @property-read Collection<int, PaymentAllocation> $allocations
 */
#[Fillable([
    'property_id',
    'declared_by_user_id',
    'amount',
    'prepaid_amount',
    'method',
    'reference',
    'screenshot_path',
    'status',
    'rejection_reason',
    'void_reason',
    'confirmed_by_user_id',
    'confirmed_at',
    'rejected_by_user_id',
    'rejected_at',
    'voided_by_user_id',
    'voided_at',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'prepaid_amount' => '0.00',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'prepaid_amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'confirmed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'voided_at' => 'datetime',
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
    public function declaredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declared_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }

    /**
     * @return HasMany<PaymentAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * @param  Builder<Payment>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', PaymentStatus::Pending);
    }
}
