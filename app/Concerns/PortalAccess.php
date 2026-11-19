<?php

namespace App\Concerns;

use App\Models\Family;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

trait PortalAccess
{
    /**
     * The students whose records the portal user may read.
     *
     * @return EloquentCollection<int, Student>
     */
    protected function portalStudents(User $user): EloquentCollection
    {
        /** @var EloquentCollection<int, Student> $students */
        $students = new EloquentCollection;

        if ($user->isStudent()) {
            $student = $user->student;

            if ($student instanceof Student) {
                $students->push($student);
            }
        }

        if ($user->isParent()) {
            $family = $user->family;

            if ($family instanceof Family) {
                $students = $students->merge(
                    $family->students()
                        ->orderBy('surname')
                        ->orderBy('first_name')
                        ->get(),
                );
            }
        }

        return $students->unique('id')->values();
    }

    /**
     * Abort unless the portal user may read the given student.
     */
    protected function ensureStudentAccess(User $user, Student $student): void
    {
        abort_unless(
            $this->portalStudents($user)->contains('id', $student->id),
            404,
        );
    }
}
