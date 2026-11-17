<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Database\Factories\AttendanceRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $attendance_register_id
 * @property int $student_id
 * @property AttendanceStatus $status
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AttendanceRegister $register
 * @property-read Student $student
 */
#[Fillable(['attendance_register_id', 'student_id', 'status', 'note'])]
class AttendanceRecord extends Model
{
    /** @use HasFactory<AttendanceRecordFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
        ];
    }

    /**
     * The register this mark belongs to.
     *
     * @return BelongsTo<AttendanceRegister, $this>
     */
    public function register(): BelongsTo
    {
        return $this->belongsTo(AttendanceRegister::class, 'attendance_register_id');
    }

    /**
     * The student this mark belongs to.
     *
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
