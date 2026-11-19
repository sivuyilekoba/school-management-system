<?php

namespace App\Http\Controllers\School;

use App\Enums\Gender;
use App\Enums\Role as RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\StoreStudentLoginRequest;
use App\Http\Requests\School\StoreStudentRequest;
use App\Http\Requests\School\UpdateStudentRequest;
use App\Models\Family;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    /**
     * List the school's students.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $students = Student::query()
            ->with(['family:id,name', 'user:id,name,email'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('surname', 'like', '%'.$search.'%')
                        ->orWhere('preferred_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('surname')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Student $student): array => [
                'id' => $student->id,
                'name' => $student->displayName(),
                'gender' => $student->gender,
                'date_of_birth' => $student->date_of_birth?->toDateString(),
                'email' => $student->email,
                'family' => $student->family?->name,
                'account' => $student->user?->email,
            ]);

        return Inertia::render('school/students/Index', [
            'students' => $students,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a new student.
     */
    public function create(): Response
    {
        return Inertia::render('school/students/Create', [
            'families' => $this->familyOptions(),
            'accounts' => $this->accountOptions(),
            'genders' => Gender::names(),
        ]);
    }

    /**
     * Store a newly created student.
     */
    public function store(StoreStudentRequest $request): RedirectResponse
    {
        Student::query()->create($request->validated());

        return to_route('school.students.index')->with('toast', [
            'type' => 'success',
            'message' => 'Student created.',
        ]);
    }

    /**
     * Show the form for editing a student.
     */
    public function edit(Student $student): Response
    {
        return Inertia::render('school/students/Edit', [
            'student' => [
                'id' => $student->id,
                'first_name' => $student->first_name,
                'surname' => $student->surname,
                'preferred_name' => $student->preferred_name,
                'gender' => $student->gender,
                'date_of_birth' => $student->date_of_birth?->toDateString(),
                'email' => $student->email,
                'family_id' => $student->family_id,
                'user_id' => $student->user_id,
            ],
            'families' => $this->familyOptions(),
            'accounts' => $this->accountOptions($student),
            'genders' => Gender::names(),
            'login' => [
                'exists' => $student->user_id !== null,
                'email' => $student->user?->email,
                'name' => $student->user?->name,
            ],
        ]);
    }

    /**
     * Create a portal login for the given student.
     */
    public function storeLogin(StoreStudentLoginRequest $request, Student $student): RedirectResponse
    {
        if ($student->user_id !== null) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => 'This student already has a login account.',
            ]);
        }

        $user = User::query()->create([
            'name' => $student->displayName(),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ]);

        $user->assignRole(RoleName::Student->value);

        $student->update(['user_id' => $user->id]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Student login created.',
        ]);
    }

    /**
     * Update the given student.
     */
    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $student->update($request->validated());

        return to_route('school.students.index')->with('toast', [
            'type' => 'success',
            'message' => 'Student updated.',
        ]);
    }

    /**
     * The families a student can be assigned to.
     *
     * @return Collection<int, Family>
     */
    private function familyOptions(): Collection
    {
        return Family::query()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * The user accounts that can be linked to a student.
     *
     * @return Collection<int, User>
     */
    private function accountOptions(?Student $student = null): Collection
    {
        $linkedAccountIds = Student::query()
            ->whereNotNull('user_id')
            ->when($student !== null, function (Builder $query) use ($student): void {
                $query->where('id', '!=', $student->id);
            })
            ->pluck('user_id');

        return User::query()
            ->whereNotIn('id', $linkedAccountIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
