<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class AccessHistoryOptionData extends Data
{
    public function __construct(public string $value, public string $label) {}
}
