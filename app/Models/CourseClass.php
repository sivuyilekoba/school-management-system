<?php

namespace App\Models;

use Database\Factories\CourseClassFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $course_id
 * @property int|null $staff_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Course $course
 * @property-read Staff|null $staff
 * @property-read int $students_count
 */
#[Fillable(['course_id', 'staff_id', 'name'])]
class CourseClass extends Model
{
    /** @use HasFactory<CourseClassFactory> */
    use HasFactory;

    /**
     * The course this class teaches.
     *
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * The staff member leading this class, if any.
     *
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * The timetable lessons scheduled for this class.
     *
     * @return HasMany<TimetableEntry, $this>
     */
    public function timetableEntries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class);
    }

    /**
     * The enrolment records for this class.
     *
     * @return HasMany<Enrolment, $this>
     */
    public function enrolments(): HasMany
    {
        return $this->hasMany(Enrolment::class);
    }

    /**
     * The students enrolled in this class.
     *
     * @return BelongsToMany<Student, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'enrolments')->withTimestamps();
    }

    /**
     * The attendance registers taken for this class.
     *
     * @return HasMany<AttendanceRegister, $this>
     */
    public function attendanceRegisters(): HasMany
    {
        return $this->hasMany(AttendanceRegister::class);
    }

    /**
     * The label shown in lists and headings.
     */
    public function displayName(): string
    {
        return $this->course->name.' ('.$this->name.')';
    }
}
