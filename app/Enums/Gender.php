<?php

namespace App\Enums;

enum Gender: string
{
    case Male = 'Male';
    case Female = 'Female';
    case Other = 'Other';
    case Unspecified = 'Unspecified';

    /**
     * Every gender option, in the order shown in forms.
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_map(fn (self $gender): string => $gender->value, self::cases());
    }
}
