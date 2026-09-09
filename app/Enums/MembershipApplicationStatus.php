<?php

namespace App\Enums;

enum MembershipApplicationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function isEditable(): bool
    {
        return $this === self::Pending || $this === self::Rejected;
    }
}
