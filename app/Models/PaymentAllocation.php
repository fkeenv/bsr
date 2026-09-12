<?php

namespace App\Models;

use App\Enums\PaymentAllocationTarget;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $payment_id
 * @property int $property_id
 * @property PaymentAllocationTarget $target
 * @property int|null $charge_id
 * @property string $amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Payment $payment
 * @property-read Property $property
 * @property-read Charge|null $charge
 */
#[Fillable([
    'payment_id',
    'property_id',
    'target',
    'charge_id',
    'amount',
])]
class PaymentAllocation extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target' => PaymentAllocationTarget::class,
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return BelongsTo<Charge, $this>
     */
    public function charge(): BelongsTo
    {
        return $this->belongsTo(Charge::class);
    }
}
