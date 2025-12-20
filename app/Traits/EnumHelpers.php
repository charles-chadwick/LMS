<?php

namespace App\Traits;

trait EnumHelpers
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

    /**
     * Get all status values as an array
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all status cases as label-value pairs for dropdowns
     *
     * @return array<array{label: string, value: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status) => ['label' => $status->value, 'value' => $status->value],
            self::cases()
        );
    }
}