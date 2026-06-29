<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum GroupType: string
{
    use EnumHelpers;

    case General = 'General';
    case Private = 'Private';

}
