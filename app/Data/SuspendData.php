<?php

namespace App\Data;

use App\Models\Suspend;
use Spatie\LaravelData\Data;

class SuspendData extends Data
{
    public function __construct(
        public int $id,
        public int $property_id,
        public string $property_label,
        public int $fee_type_id,
        public string $fee_type_name,
        public int $starts_year,
        public int $starts_month,
        public ?int $ends_year,
        public ?int $ends_month,
    ) {}

    public static function fromModel(Suspend $suspend): self
    {
        $suspend->loadMissing(['property', 'feeType']);

        return new self(
            id: $suspend->id,
            property_id: $suspend->property_id,
            property_label: "Block {$suspend->property->block} · Lot {$suspend->property->lot}",
            fee_type_id: $suspend->fee_type_id,
            fee_type_name: $suspend->feeType->name,
            starts_year: $suspend->starts_year,
            starts_month: $suspend->starts_month,
            ends_year: $suspend->ends_year,
            ends_month: $suspend->ends_month,
        );
    }
}
