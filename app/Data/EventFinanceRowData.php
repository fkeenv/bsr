<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class EventFinanceRowData extends Data
{
    /** @param list<string> $work */
    public function __construct(
        public int $id,
        public EventRegistrationData $registration,
        public EventFinancialData $financial,
        public array $work,
        public string $submission_key,
    ) {}
}
