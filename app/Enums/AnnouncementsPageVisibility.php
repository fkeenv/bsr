<?php

namespace App\Enums;

enum AnnouncementsPageVisibility: string
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
            self::Public => 'Anyone can read the published feed, including guests.',
            self::Private => 'Only User Accounts with a live Membership (and Officers) can read the feed.',
            self::Hidden => 'The feed is not available. Officers can still manage drafts and published items.',
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
