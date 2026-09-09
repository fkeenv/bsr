<?php

namespace App\Enums;

enum MembershipApplicationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function isOpen(): bool
    {
        return $this === self::Pending || $this === self::Rejected;
    }

    public function isAwaitingReview(): bool
    {
        return $this === self::Pending;
    }

    public function canApplicantEdit(): bool
    {
        return $this === self::Pending || $this === self::Rejected;
    }
}
