<?php

namespace App\Models;

use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $membership_application_id
 * @property int $year
 * @property string $make
 * @property string $model
 * @property string $plate
 * @property string $sticker_number
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'membership_application_id',
    'year',
    'make',
    'model',
    'plate',
    'sticker_number',
])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<MembershipApplication, $this>
     */
    public function membershipApplication(): BelongsTo
    {
        return $this->belongsTo(MembershipApplication::class);
    }
}
