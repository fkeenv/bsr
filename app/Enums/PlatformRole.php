<?php

namespace App\Enums;

enum PlatformRole: string
{
    case SuperAdmin = 'super-admin';
    case Administrator = 'administrator';
    case Officer = 'officer';
    case Member = 'member';
    case User = 'user';

    public function level(): int
    {
        return match ($this) {
            self::SuperAdmin => 0,
            self::Administrator => 1,
            self::Officer => 2,
            self::Member => 3,
            self::User => 4,
        };
    }

    /**
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [
            self::SuperAdmin,
            self::Administrator,
            self::Officer,
            self::Member,
            self::User,
        ];
    }
}
