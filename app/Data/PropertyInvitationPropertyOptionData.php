<?php

namespace App\Data;

use App\Models\Property;
use Spatie\LaravelData\Data;

class PropertyInvitationPropertyOptionData extends Data
{
    public function __construct(
        public int $id,
        public string $label,
    ) {}

    public static function fromModel(Property $property): self
    {
        return new self(
            id: $property->id,
            label: 'Block '.$property->block.' · Lot '.$property->lot,
        );
    }
}
