<?php

namespace App\Models;

use App\Support\BillingPeriod;
use Database\Factories\SuspendFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $property_id
 * @property int $fee_type_id
 * @property int $starts_year
 * @property int $starts_month
 * @property int|null $ends_year
 * @property int|null $ends_month
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Property $property
 * @property-read FeeType $feeType
 */
#[Fillable([
    'property_id',
    'fee_type_id',
    'starts_year',
    'starts_month',
    'ends_year',
    'ends_month',
])]
class Suspend extends Model
{
    /** @use HasFactory<SuspendFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return BelongsTo<FeeType, $this>
     */
    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    public function startsOn(): BillingPeriod
    {
        return new BillingPeriod($this->starts_year, $this->starts_month);
    }

    public function endsOn(): ?BillingPeriod
    {
        if ($this->ends_year === null || $this->ends_month === null) {
            return null;
        }

        return new BillingPeriod($this->ends_year, $this->ends_month);
    }

    public function covers(BillingPeriod $period): bool
    {
        if ($period->isBefore($this->startsOn())) {
            return false;
        }

        $endsOn = $this->endsOn();

        if ($endsOn === null) {
            return true;
        }

        return ! $period->isAfter($endsOn);
    }
}
