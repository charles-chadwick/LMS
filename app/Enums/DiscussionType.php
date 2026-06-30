<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum DiscussionType: string
{
    use EnumHelpers;

    case General = 'General';
    case Announcement = 'Announcement';
}
