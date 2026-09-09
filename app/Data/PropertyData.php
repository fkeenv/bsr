<?php

namespace App\Data;

use App\Models\Property;
use Spatie\LaravelData\Data;

class PropertyData extends Data
{
    public function __construct(
        public int $id,
        public string $block,
        public string $lot,
        public ?string $street_address,
        public ?string $recorded_owner_name,
        public string $opening_balance,
        public bool $opening_balance_is_frozen,
        public bool $is_active,
        public bool $has_been_charged,
    ) {}

    public static function fromModel(Property $property): self
    {
        return new self(
            id: $property->id,
            block: $property->block,
            lot: $property->lot,
            street_address: $property->street_address,
            recorded_owner_name: $property->recorded_owner_name,
            opening_balance: $property->opening_balance,
            opening_balance_is_frozen: $property->openingBalanceIsFrozen(),
            is_active: $property->is_active,
            has_been_charged: $property->hasBeenCharged(),
        );
    }
}
