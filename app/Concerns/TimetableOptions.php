<?php

namespace App\Concerns;

use App\Models\TimetablePeriod;
use Illuminate\Support\Collection;

trait TimetableOptions
{
    /**
     * The teaching days shown on timetables.
     *
     * @return array<int, array{value: int, label: string}>
     */
    protected function dayOptions(): array
    {
        return [
            ['value' => 1, 'label' => 'Monday'],
            ['value' => 2, 'label' => 'Tuesday'],
            ['value' => 3, 'label' => 'Wednesday'],
            ['value' => 4, 'label' => 'Thursday'],
            ['value' => 5, 'label' => 'Friday'],
        ];
    }

    /**
     * The school's timetable periods in teaching order.
     *
     * @return Collection<int, array{id: int, name: string, start_time: string, end_time: string}>
     */
    protected function periodOptions(): Collection
    {
        return TimetablePeriod::query()
            ->orderBy('start_time')
            ->get(['id', 'name', 'start_time', 'end_time'])
            ->map(fn (TimetablePeriod $period): array => [
                'id' => $period->id,
                'name' => $period->name,
                'start_time' => $period->start_time,
                'end_time' => $period->end_time,
            ])
            ->values();
    }
}
