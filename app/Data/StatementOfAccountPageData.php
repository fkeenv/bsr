<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class StatementOfAccountPageData extends Data
{
    /**
     * @param  array{id: int, label: string}  $property
     * @param  list<PaymentData>  $pending_declarations
     * @param  list<StatementPeriodData>  $periods
     * @param  list<StatementPropertyOptionData>  $switcher
     */
    public function __construct(
        public array $property,
        public string $outstanding_balance,
        public string $remaining_opening_balance,
        public string $prepaid_balance,
        public array $pending_declarations,
        public array $periods,
        public ?int $selected_charge_id,
        public ?StatementPeriodData $selected_period,
        public array $switcher,
    ) {}
}
