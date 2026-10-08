<?php

namespace App\Enums;

enum AnnouncementVisibility: string
{
    case Public = 'public';
    case Private = 'private';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Public',
            self::Private => 'Private',
            self::Hidden => 'Hidden',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Public => 'Anyone can read this Announcement once published, including guests.',
            self::Private => 'Only live Membership holders and Officers can read this Announcement.',
            self::Hidden => 'Only Officers can read this Announcement. It stays hidden from everyone else.',
        };
    }

    /**
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [
            self::Public,
            self::Private,
            self::Hidden,
        ];
    }
}
