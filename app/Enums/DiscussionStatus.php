<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum DiscussionStatus: string
{
    use EnumHelpers;

    case Open = 'Open';
    case Closed = 'Closed';
}
