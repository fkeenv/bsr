<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class IssuedPropertyInvitationData extends Data
{
    public function __construct(public string $token, public string $code) {}
}
