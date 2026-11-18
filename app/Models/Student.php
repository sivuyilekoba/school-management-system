<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $family_id
 * @property int|null $user_id
 * @property string $first_name
 * @property string $surname
 * @property string|null $preferred_name
 * @property string $gender
 * @property Carbon|null $date_of_birth
 * @property string|null $email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Family|null $family
 * @property-read User|null $user
 * @property-read int $enrolments_count
 * @property-read Collection<int, ReportCard> $reportCards
 */
#[Fillable([
    'family_id',
    'user_id',
    'first_name',
    'surname',
    'preferred_name',
    'gender',
    'date_of_birth',
    'email',
])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    /**
     * The family this student belongs to.
     *
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * The user account linked to this student, if any.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The class enrolments for this student.
     *
     * @return HasMany<Enrolment, $this>
     */
    public function enrolments(): HasMany
    {
        return $this->hasMany(Enrolment::class);
    }

    /**
     * The classes this student is enrolled in.
     *
     * @return BelongsToMany<CourseClass, $this>
     */
    public function courseClasses(): BelongsToMany
    {
        return $this->belongsToMany(CourseClass::class, 'enrolments')->withTimestamps();
    }

    /**
     * The attendance marks recorded for this student.
     *
     * @return HasMany<AttendanceRecord, $this>
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * The assessment marks recorded for this student.
     *
     * @return HasMany<AssessmentMark, $this>
     */
    public function assessmentMarks(): HasMany
    {
        return $this->hasMany(AssessmentMark::class);
    }

    /**
     * The report cards written for this student.
     *
     * @return HasMany<ReportCard, $this>
     */
    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class);
    }

    /**
     * The name shown in lists and headings.
     */
    public function displayName(): string
    {
        $first = $this->preferred_name ?: $this->first_name;

        return trim($first.' '.$this->surname);
    }
}
