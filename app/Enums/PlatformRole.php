<?php

namespace App\Enums;

enum PlatformRole: string
{
    case SuperAdmin = 'super-admin';
    case Administrator = 'administrator';
    case Officer = 'officer';
    case Member = 'member';
    case User = 'user';

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
