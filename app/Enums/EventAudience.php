<?php

namespace App\Enums;

enum EventAudience: string
{
    case Public = 'public';
    case MembersOnly = 'members-only';

    public function label(): string
    {
        return $this === self::Public ? 'Public' : 'Members only';
    }
}
