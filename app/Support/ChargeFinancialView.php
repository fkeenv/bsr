<?php

namespace App\Support;

use App\Enums\PeriodStatus;
use App\Models\Charge;

readonly class ChargeFinancialView
{
    public function __construct(
        public Charge $charge,
        public string $total,
        public string $remaining,
        public PeriodStatus $status,
    ) {}
}
