<?php

namespace App\Enums;

enum AnnouncementAudience: string
{
    case Everyone = 'everyone';
    case Staff = 'staff';
    case Students = 'students';
    case Families = 'families';

    /**
     * The human-readable label for the audience.
     */
    public function label(): string
    {
        return match ($this) {
            self::Everyone => 'Everyone',
            self::Staff => 'Staff',
            self::Students => 'Students',
            self::Families => 'Families',
        };
    }

    /**
     * The audiences as value/label pairs for forms.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $audience): array => [
            'value' => $audience->value,
            'label' => $audience->label(),
        ], self::cases());
    }
}
