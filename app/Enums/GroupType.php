<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum GroupType: string
{
    use EnumHelpers;

    case Instructor = 'Instructor';
    case Student = 'Student';

    /**
     * Map this group type to the user role its members must hold.
     */
    public function toUserRole(): UserRole
    {
        return match ($this) {
            self::Instructor => UserRole::Instructor,
            self::Student => UserRole::Student,
        };
    }
}
