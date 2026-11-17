<?php

namespace App\Models;

use Database\Factories\EnrolmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $course_class_id
 * @property int $student_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CourseClass $courseClass
 * @property-read Student $student
 */
#[Fillable(['course_class_id', 'student_id'])]
class Enrolment extends Model
{
    /** @use HasFactory<EnrolmentFactory> */
    use HasFactory;

    /**
     * The class the student is enrolled in.
     *
     * @return BelongsTo<CourseClass, $this>
     */
    public function courseClass(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class);
    }

    /**
     * The student enrolled in the class.
     *
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
