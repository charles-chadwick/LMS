<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum CourseStatus: string
{
    use EnumHelpers;

    case Draft = 'Draft';
    case Published = 'Published';
    case Archived = 'Archived';
}