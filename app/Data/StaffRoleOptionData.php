<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class StaffRoleOptionData extends Data
{
    public function __construct(public string $name, public string $label, public bool $assigned, public bool $can_manage) {}
}
