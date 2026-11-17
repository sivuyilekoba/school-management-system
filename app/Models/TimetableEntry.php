<?php

namespace App\Models;

use Database\Factories\TimetableEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $course_class_id
 * @property int $timetable_period_id
 * @property int $day_of_week
 * @property string|null $room
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CourseClass $courseClass
 * @property-read TimetablePeriod $period
 */
#[Fillable(['course_class_id', 'timetable_period_id', 'day_of_week', 'room'])]
class TimetableEntry extends Model
{
    /** @use HasFactory<TimetableEntryFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    /**
     * The class this lesson belongs to.
     *
     * @return BelongsTo<CourseClass, $this>
     */
    public function courseClass(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class);
    }

    /**
     * The period this lesson is scheduled in.
     *
     * @return BelongsTo<TimetablePeriod, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(TimetablePeriod::class, 'timetable_period_id');
    }
}
