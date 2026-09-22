<?php

namespace App\Models;

use Database\Factories\PropertyProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $property_id
 * @property list<array{name: string}> $household_members
 * @property list<array{name: string, contact_number: string, relationship: string}> $emergency_contacts
 * @property list<array{year: int, make: string, model: string, plate: string, sticker_number: string}> $vehicles
 * @property Carbon $saved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'property_id',
    'household_members',
    'emergency_contacts',
    'vehicles',
    'saved_at',
])]
class PropertyProfile extends Model
{
    /** @use HasFactory<PropertyProfileFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'household_members' => 'array',
            'emergency_contacts' => 'array',
            'vehicles' => 'array',
            'saved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
