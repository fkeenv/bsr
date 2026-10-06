<?php

namespace App\Support;

readonly class PropertyFinancialView
{
    /**
     * @param  list<ChargeFinancialView>  $charges  Chronological Charge views for this read.
     * @param  array{outstanding_balance: string, remaining_opening_balance: string, prepaid_balance: string}  $balances
     */
    public function __construct(
        public array $charges,
        public array $balances,
    ) {}
}
