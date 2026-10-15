<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class EventAttendeeLookupPageData extends Data
{
    /** @param list<EventAttendeeLookupData> $data */
    public function __construct(public array $data, public int $current_page, public int $last_page, public int $total) {}
}
