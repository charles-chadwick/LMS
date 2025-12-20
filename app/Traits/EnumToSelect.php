<?php

namespace App\Traits;

trait EnumToSelect
{
    public static function toSelect() : array
    {
        return array_map(
            fn(self $case) => [
                'value' => $case->value,
                'label' => $case->name,
            ],
            self::cases()
        );
    }
}