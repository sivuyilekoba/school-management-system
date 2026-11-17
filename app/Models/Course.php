<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $short_name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int $classes_count
 * @property-read Collection<int, CourseClass> $classes
 */
#[Fillable(['name', 'short_name', 'description'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    /**
     * The teaching classes offered for this course.
     *
     * @return HasMany<CourseClass, $this>
     */
    public function classes(): HasMany
    {
        return $this->hasMany(CourseClass::class);
    }
}
