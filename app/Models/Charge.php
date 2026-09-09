<?php

namespace App\Models;

use Database\Factories\ChargeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * @property int $id
 * @property int $property_id
 * @property int $year
 * @property int $month
 * @property Carbon|null $frozen_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Property $property
 * @property-read Collection<int, ChargeLine> $lines
 */
#[Fillable([
    'property_id',
    'year',
    'month',
])]
class Charge extends Model
{
    /** @use HasFactory<ChargeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'frozen_at' => 'datetime',
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
     * @return HasMany<ChargeLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(ChargeLine::class);
    }

    public function isFrozen(): bool
    {
        return $this->frozen_at !== null;
    }

    /** Called by Payment confirmation once a confirmed Payment exists against this Charge (#payment). */
    public function freeze(): void
    {
        if ($this->isFrozen()) {
            return;
        }

        $this->forceFill([
            'frozen_at' => now(),
        ])->save();
    }

    /** Called when a confirmed Payment is voided (#payment). */
    public function unfreeze(): void
    {
        if (! $this->isFrozen()) {
            return;
        }

        $this->forceFill([
            'frozen_at' => null,
        ])->save();
    }

    public function assertMayBeEdited(): void
    {
        if ($this->isFrozen()) {
            throw new InvalidArgumentException('Charge is frozen after a confirmed Payment.');
        }
    }
}
