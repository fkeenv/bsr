<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class StatementPeriodPaymentData extends Data
{
    public function __construct(
        public int $id,
        public string $amount,
        public string $method,
        public ?string $reference,
        public string $status,
        public ?string $recorded_on,
    ) {}
}
