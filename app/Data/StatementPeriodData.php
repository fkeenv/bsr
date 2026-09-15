<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class StatementPeriodData extends Data
{
    /**
     * @param  list<ChargeLineData>  $lines
     * @param  list<StatementPeriodPaymentData>  $payments
     */
    public function __construct(
        public int $charge_id,
        public int $year,
        public int $month,
        public string $label,
        public string $status,
        public string $remaining,
        public string $charge_total,
        public array $lines,
        public array $payments,
    ) {}
}
