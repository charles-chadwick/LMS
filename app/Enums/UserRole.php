<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum UserRole: string
{
    use EnumHelpers;

    case Admin = 'Admin';
    case Instructor = 'Instructor';
    case Student = 'Student';

    /**
     * Return the backing values for the given roles, for use in queries
     * against the role `name` column.
     *
     * @return array<int, string>
     */
    public static function values(UserRole ...$roles): array
    {
        return array_map(fn (UserRole $role): string => $role->value, $roles);
    }
}
