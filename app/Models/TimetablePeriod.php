<?php

namespace App\Models;

use Database\Factories\TimetablePeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $start_time
 * @property string $end_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TimetableEntry> $entries
 */
#[Fillable(['name', 'start_time', 'end_time'])]
class TimetablePeriod extends Model
{
    /** @use HasFactory<TimetablePeriodFactory> */
    use HasFactory;

    /**
     * The timetable entries scheduled in this period.
     *
     * @return HasMany<TimetableEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class);
    }
}
