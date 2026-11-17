<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AssessmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $course_class_id
 * @property string $name
 * @property Carbon $assessed_on
 * @property string $max_score
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CourseClass $courseClass
 * @property-read Collection<int, AssessmentMark> $marks
 * @property-read int $marks_count
 */
#[Fillable(['course_class_id', 'name', 'assessed_on', 'max_score'])]
class Assessment extends Model
{
    /** @use HasFactory<AssessmentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assessed_on' => 'date',
            'max_score' => 'decimal:2',
        ];
    }

    /**
     * Store the assessment date as a plain calendar date.
     *
     * @return Attribute<Carbon, string>
     */
    protected function assessedOn(): Attribute
    {
        return Attribute::make(
            set: fn (string|\DateTimeInterface $value): string => CarbonImmutable::parse($value)->toDateString(),
        );
    }

    /**
     * The class this assessment belongs to.
     *
     * @return BelongsTo<CourseClass, $this>
     */
    public function courseClass(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class);
    }

    /**
     * The student marks recorded for this assessment.
     *
     * @return HasMany<AssessmentMark, $this>
     */
    public function marks(): HasMany
    {
        return $this->hasMany(AssessmentMark::class);
    }
}
