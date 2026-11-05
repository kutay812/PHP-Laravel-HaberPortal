<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'Super Admin';
    case Admin = 'Admin';
    case Editor = 'Editor';
    case User = 'User';

    /**
     * @return list<string>
     */
    public static function staffRoles(): array
    {
        return [
            self::SuperAdmin->value,
            self::Admin->value,
            self::Editor->value,
        ];
    }

    /**
     * @return list<string>
     */
    public static function adminRoles(): array
    {
        return [
            self::SuperAdmin->value,
            self::Admin->value,
        ];
    }
}

