<?php

namespace App\Enums;

enum Role: string
{
    case Administrator = 'Administrator';
    case Teacher = 'Teacher';
    case Student = 'Student';
    case Parent = 'Parent';
    case SupportStaff = 'Support Staff';

    /**
     * Every role name, mirroring Gibbon's core roles.
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_map(fn (self $role): string => $role->value, self::cases());
    }

    /**
     * The roles whose permissions can be edited from the permission matrix.
     *
     * @return array<int, string>
     */
    public static function managedNames(): array
    {
        return array_values(array_filter(
            self::names(),
            fn (string $name): bool => $name !== self::Administrator->value,
        ));
    }
}
