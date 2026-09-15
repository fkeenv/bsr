<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class StatementPropertyOptionData extends Data
{
    public function __construct(
        public int $property_id,
        public string $label,
        public string $outstanding_balance,
    ) {}
}
