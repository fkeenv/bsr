<?php

namespace App\Data;

use App\Models\Property;
use App\Models\PropertyProfile;
use Spatie\LaravelData\Data;

class PropertyProfileData extends Data
{
    /**
     * @param  list<array{name: string}>  $household_members
     * @param  list<array{name: string, contact_number: string, relationship: string}>  $emergency_contacts
     * @param  list<array{year: int, make: string, model: string, plate: string, sticker_number: string}>  $vehicles
     */
    public function __construct(
        public int $property_id,
        public string $property_label,
        public ?string $saved_at,
        public array $household_members,
        public array $emergency_contacts,
        public array $vehicles,
    ) {}

    public static function fromProperty(Property $property): self
    {
        $profile = PropertyProfile::query()->whereBelongsTo($property)->first();

        return new self(
            property_id: $property->id,
            property_label: 'Block '.$property->block.' · Lot '.$property->lot,
            saved_at: $profile?->saved_at->toIso8601String(),
            household_members: $profile->household_members ?? [],
            emergency_contacts: $profile->emergency_contacts ?? [],
            vehicles: $profile->vehicles ?? [],
        );
    }
}
