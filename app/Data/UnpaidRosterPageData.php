<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class UnpaidRosterPageData extends Data
{
    /**
     * @param  array{year: int, month: int, label: string, key: string}  $this_billing_period
     * @param  list<UnpaidRosterRowData>  $rows
     * @param  array{
     *     blocks: list<string>,
     *     owes_for: list<array{value: string, label: string}>
     * }  $filter_options
     * @param  array{
     *     block: string|null,
     *     lot: string|null,
     *     status: string|null,
     *     owes_for: string|null,
     *     property: int|null,
     *     charge: int|null
     * }  $values
     */
    public function __construct(
        public array $this_billing_period,
        public int $roster_count,
        public int $unpaid_count,
        public bool $filters_active,
        public ?string $empty_state,
        public array $rows,
        public ?StatementOfAccountPageData $selected,
        public array $filter_options,
        public array $values,
    ) {}
}
