<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Rescheduled = 'rescheduled';
    case Cancelled = 'cancelled';

    public function isPublished(): bool
    {
        return in_array($this, self::publishedStates(), true);
    }

    /** @return list<self> */
    public static function publishedStates(): array
    {
        return [self::Published, self::Rescheduled];
    }
}
