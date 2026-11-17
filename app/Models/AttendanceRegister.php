<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AttendanceRegisterFactory;
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
 * @property Carbon $date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CourseClass $courseClass
 * @property-read Collection<int, AttendanceRecord> $records
 */
#[Fillable(['course_class_id', 'date'])]
class AttendanceRegister extends Model
{
    /** @use HasFactory<AttendanceRegisterFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * Store the register date as a plain calendar date.
     *
     * @return Attribute<Carbon, string>
     */
    protected function date(): Attribute
    {
        return Attribute::make(
            set: fn (string|\DateTimeInterface $value): string => CarbonImmutable::parse($value)->toDateString(),
        );
    }

    /**
     * The class this register belongs to.
     *
     * @return BelongsTo<CourseClass, $this>
     */
    public function courseClass(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class);
    }

    /**
     * The attendance marks recorded in this register.
     *
     * @return HasMany<AttendanceRecord, $this>
     */
    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
