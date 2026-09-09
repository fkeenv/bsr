<?php

namespace App\Data;

use App\Models\FeeType;
use Spatie\LaravelData\Data;

class FeeTypeData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $amount,
        public bool $is_retired,
    ) {}

    public static function fromModel(FeeType $feeType): self
    {
        return new self(
            id: $feeType->id,
            name: $feeType->name,
            amount: $feeType->amount,
            is_retired: $feeType->isRetired(),
        );
    }
}
