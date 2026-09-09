<?php

namespace App\Data;

use App\Models\ChargeLine;
use Spatie\LaravelData\Data;

class ChargeLineData extends Data
{
    public function __construct(
        public int $id,
        public ?int $fee_type_id,
        public string $fee_type_name,
        public string $amount,
    ) {}

    public static function fromModel(ChargeLine $line): self
    {
        return new self(
            id: $line->id,
            fee_type_id: $line->fee_type_id,
            fee_type_name: $line->fee_type_name,
            amount: $line->amount,
        );
    }
}
