<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class UnpaidRosterRowData extends Data
{
    public function __construct(
        public int $property_id,
        public string $block,
        public string $lot,
        public string $label,
        public ?string $recorded_owner_name,
        public string $outstanding_balance,
        public string $remaining_opening_balance,
        public string $this_period_status,
        public ?string $oldest_open_label,
    ) {}
}
