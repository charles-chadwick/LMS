<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum DiscussionPostStatus: string
{
    use EnumHelpers;

    case Draft = 'Draft';
    case Published = 'Published';
}
