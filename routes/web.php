<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\Portal\AnnouncementController as PortalAnnouncementController;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\Portal\ReportCardController as PortalReportCardController;
use App\Http\Controllers\School\AnnouncementController;
use App\Http\Controllers\School\AssessmentController;
use App\Http\Controllers\School\AttendanceController;
use App\Http\Controllers\School\ClassEnrolmentController;
use App\Http\Controllers\School\CourseClassController;
use App\Http\Controllers\School\CourseController;
use App\Http\Controllers\School\FamilyController;
use App\Http\Controllers\School\ReportCardController;
use App\Http\Controllers\School\RolePermissionsController;
use App\Http\Controllers\School\SchoolSettingsController;
use App\Http\Controllers\School\SchoolYearController;
use App\Http\Controllers\School\StaffController;
use App\Http\Controllers\School\StudentController;
use App\Http\Controllers\School\TermController;
use App\Http\Controllers\School\TimetableController;
use App\Http\Controllers\School\TimetableEntryController;
use App\Http\Controllers\School\TimetablePeriodController;
use App\Http\Controllers\School\UserController;
use App\Http\Middleware\EnsureMessagingAccess;
use App\Http\Middleware\EnsurePortalUser;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('school/settings', [SchoolSettingsController::class, 'edit'])
        ->middleware('can:settings.manage')
        ->name('school.settings.edit');

    Route::patch('school/settings', [SchoolSettingsController::class, 'update'])
        ->middleware('can:settings.manage')
        ->name('school.settings.update');

    Route::get('school/permissions', [RolePermissionsController::class, 'edit'])
        ->middleware('can:roles.manage')
        ->name('school.permissions.edit');

    Route::put('school/permissions', [RolePermissionsController::class, 'update'])
        ->middleware('can:roles.manage')
        ->name('school.permissions.update');

    Route::get('school/users', [UserController::class, 'index'])
        ->middleware('can:users.view')
        ->name('school.users.index');

    Route::get('school/users/create', [UserController::class, 'create'])
        ->middleware('can:users.manage')
        ->name('school.users.create');

    Route::post('school/users', [UserController::class, 'store'])
        ->middleware('can:users.manage')
        ->name('school.users.store');

    Route::get('school/users/{user}/edit', [UserController::class, 'edit'])
        ->middleware('can:users.manage')
        ->name('school.users.edit');

    Route::put('school/users/{user}', [UserController::class, 'update'])
        ->middleware('can:users.manage')
        ->name('school.users.update');

    Route::get('school/students', [StudentController::class, 'index'])
        ->middleware('can:people.students.view')
        ->name('school.students.index');

    Route::get('school/students/create', [StudentController::class, 'create'])
        ->middleware('can:people.students.manage')
        ->name('school.students.create');

    Route::post('school/students', [StudentController::class, 'store'])
        ->middleware('can:people.students.manage')
        ->name('school.students.store');

    Route::get('school/students/{student}/edit', [StudentController::class, 'edit'])
        ->middleware('can:people.students.manage')
        ->name('school.students.edit');

    Route::put('school/students/{student}', [StudentController::class, 'update'])
        ->middleware('can:people.students.manage')
        ->name('school.students.update');

    Route::post('school/students/{student}/login', [StudentController::class, 'storeLogin'])
        ->middleware('can:people.students.manage')
        ->name('school.students.login.store');

    Route::get('school/staff', [StaffController::class, 'index'])
        ->middleware('can:people.staff.view')
        ->name('school.staff.index');

    Route::get('school/staff/create', [StaffController::class, 'create'])
        ->middleware('can:people.staff.manage')
        ->name('school.staff.create');

    Route::post('school/staff', [StaffController::class, 'store'])
        ->middleware('can:people.staff.manage')
        ->name('school.staff.store');

    Route::get('school/staff/{staff}/edit', [StaffController::class, 'edit'])
        ->middleware('can:people.staff.manage')
        ->name('school.staff.edit');

    Route::put('school/staff/{staff}', [StaffController::class, 'update'])
        ->middleware('can:people.staff.manage')
        ->name('school.staff.update');

    Route::get('school/families', [FamilyController::class, 'index'])
        ->middleware('can:people.families.view')
        ->name('school.families.index');

    Route::get('school/families/create', [FamilyController::class, 'create'])
        ->middleware('can:people.families.manage')
        ->name('school.families.create');

    Route::post('school/families', [FamilyController::class, 'store'])
        ->middleware('can:people.families.manage')
        ->name('school.families.store');

    Route::get('school/families/{family}/edit', [FamilyController::class, 'edit'])
        ->middleware('can:people.families.manage')
        ->name('school.families.edit');

    Route::put('school/families/{family}', [FamilyController::class, 'update'])
        ->middleware('can:people.families.manage')
        ->name('school.families.update');

    Route::post('school/families/{family}/login', [FamilyController::class, 'storeLogin'])
        ->middleware('can:people.families.manage')
        ->name('school.families.login.store');

    Route::get('school/calendar', [SchoolYearController::class, 'index'])
        ->middleware('can:calendar.view')
        ->name('school.calendar.index');

    Route::get('school/calendar/years/create', [SchoolYearController::class, 'create'])
        ->middleware('can:calendar.manage')
        ->name('school.calendar.years.create');

    Route::post('school/calendar/years', [SchoolYearController::class, 'store'])
        ->middleware('can:calendar.manage')
        ->name('school.calendar.years.store');

    Route::get('school/calendar/years/{schoolYear}/edit', [SchoolYearController::class, 'edit'])
        ->middleware('can:calendar.manage')
        ->name('school.calendar.years.edit');

    Route::put('school/calendar/years/{schoolYear}', [SchoolYearController::class, 'update'])
        ->middleware('can:calendar.manage')
        ->name('school.calendar.years.update');

    Route::get('school/calendar/years/{schoolYear}/terms/create', [TermController::class, 'create'])
        ->middleware('can:calendar.manage')
        ->name('school.calendar.terms.create');

    Route::post('school/calendar/years/{schoolYear}/terms', [TermController::class, 'store'])
        ->middleware('can:calendar.manage')
        ->name('school.calendar.terms.store');

    Route::get('school/calendar/terms/{term}/edit', [TermController::class, 'edit'])
        ->middleware('can:calendar.manage')
        ->name('school.calendar.terms.edit');

    Route::put('school/calendar/terms/{term}', [TermController::class, 'update'])
        ->middleware('can:calendar.manage')
        ->name('school.calendar.terms.update');

    Route::get('school/courses', [CourseController::class, 'index'])
        ->middleware('can:curriculum.view')
        ->name('school.courses.index');

    Route::get('school/courses/create', [CourseController::class, 'create'])
        ->middleware('can:curriculum.manage')
        ->name('school.courses.create');

    Route::post('school/courses', [CourseController::class, 'store'])
        ->middleware('can:curriculum.manage')
        ->name('school.courses.store');

    Route::get('school/courses/{course}/edit', [CourseController::class, 'edit'])
        ->middleware('can:curriculum.manage')
        ->name('school.courses.edit');

    Route::put('school/courses/{course}', [CourseController::class, 'update'])
        ->middleware('can:curriculum.manage')
        ->name('school.courses.update');

    Route::get('school/courses/{course}/classes/create', [CourseClassController::class, 'create'])
        ->middleware('can:curriculum.manage')
        ->name('school.classes.create');

    Route::post('school/courses/{course}/classes', [CourseClassController::class, 'store'])
        ->middleware('can:curriculum.manage')
        ->name('school.classes.store');

    Route::get('school/classes/{courseClass}/edit', [CourseClassController::class, 'edit'])
        ->middleware('can:curriculum.manage')
        ->name('school.classes.edit');

    Route::put('school/classes/{courseClass}', [CourseClassController::class, 'update'])
        ->middleware('can:curriculum.manage')
        ->name('school.classes.update');

    Route::get('school/classes/{courseClass}/students', [ClassEnrolmentController::class, 'index'])
        ->middleware('can:curriculum.view')
        ->name('school.classes.students.index');

    Route::post('school/classes/{courseClass}/students', [ClassEnrolmentController::class, 'store'])
        ->middleware('can:curriculum.enrolment.manage')
        ->name('school.classes.students.store');

    Route::delete('school/classes/{courseClass}/students/{student}', [ClassEnrolmentController::class, 'destroy'])
        ->middleware('can:curriculum.enrolment.manage')
        ->name('school.classes.students.destroy');

    Route::get('school/timetable', [TimetableController::class, 'index'])
        ->middleware('can:timetable.view')
        ->name('school.timetable.index');

    Route::get('school/timetable/periods', [TimetablePeriodController::class, 'index'])
        ->middleware('can:timetable.manage')
        ->name('school.timetable.periods.index');

    Route::get('school/timetable/periods/create', [TimetablePeriodController::class, 'create'])
        ->middleware('can:timetable.manage')
        ->name('school.timetable.periods.create');

    Route::post('school/timetable/periods', [TimetablePeriodController::class, 'store'])
        ->middleware('can:timetable.manage')
        ->name('school.timetable.periods.store');

    Route::get('school/timetable/periods/{period}/edit', [TimetablePeriodController::class, 'edit'])
        ->middleware('can:timetable.manage')
        ->name('school.timetable.periods.edit');

    Route::put('school/timetable/periods/{period}', [TimetablePeriodController::class, 'update'])
        ->middleware('can:timetable.manage')
        ->name('school.timetable.periods.update');

    Route::get('school/timetable/classes/{courseClass}/entries/create', [TimetableEntryController::class, 'create'])
        ->middleware('can:timetable.manage')
        ->name('school.timetable.entries.create');

    Route::post('school/timetable/classes/{courseClass}/entries', [TimetableEntryController::class, 'store'])
        ->middleware('can:timetable.manage')
        ->name('school.timetable.entries.store');

    Route::get('school/timetable/entries/{entry}/edit', [TimetableEntryController::class, 'edit'])
        ->middleware('can:timetable.manage')
        ->name('school.timetable.entries.edit');

    Route::put('school/timetable/entries/{entry}', [TimetableEntryController::class, 'update'])
        ->middleware('can:timetable.manage')
        ->name('school.timetable.entries.update');

    Route::delete('school/timetable/entries/{entry}', [TimetableEntryController::class, 'destroy'])
        ->middleware('can:timetable.manage')
        ->name('school.timetable.entries.destroy');

    Route::get('school/attendance', [AttendanceController::class, 'index'])
        ->middleware('can:attendance.view')
        ->name('school.attendance.index');

    Route::get('school/attendance/classes/{courseClass}', [AttendanceController::class, 'show'])
        ->middleware('can:attendance.view')
        ->name('school.attendance.show');

    Route::put('school/attendance/classes/{courseClass}', [AttendanceController::class, 'save'])
        ->middleware('can:attendance.record')
        ->name('school.attendance.save');

    Route::get('school/assessment', [AssessmentController::class, 'index'])
        ->middleware('can:assessment.view')
        ->name('school.assessment.index');

    Route::get('school/assessment/classes/{courseClass}', [AssessmentController::class, 'classIndex'])
        ->middleware('can:assessment.view')
        ->name('school.assessment.classes.index');

    Route::get('school/assessment/classes/{courseClass}/assessments/create', [AssessmentController::class, 'create'])
        ->middleware('can:assessment.manage')
        ->name('school.assessment.assessments.create');

    Route::post('school/assessment/classes/{courseClass}/assessments', [AssessmentController::class, 'store'])
        ->middleware('can:assessment.manage')
        ->name('school.assessment.assessments.store');

    Route::get('school/assessment/assessments/{assessment}/edit', [AssessmentController::class, 'edit'])
        ->middleware('can:assessment.manage')
        ->name('school.assessment.assessments.edit');

    Route::put('school/assessment/assessments/{assessment}', [AssessmentController::class, 'update'])
        ->middleware('can:assessment.manage')
        ->name('school.assessment.assessments.update');

    Route::delete('school/assessment/assessments/{assessment}', [AssessmentController::class, 'destroy'])
        ->middleware('can:assessment.manage')
        ->name('school.assessment.assessments.destroy');

    Route::get('school/assessment/assessments/{assessment}/marks', [AssessmentController::class, 'marks'])
        ->middleware('can:assessment.view')
        ->name('school.assessment.marks');

    Route::put('school/assessment/assessments/{assessment}/marks', [AssessmentController::class, 'saveMarks'])
        ->middleware('can:assessment.manage')
        ->name('school.assessment.marks.save');

    Route::get('school/reports', [ReportCardController::class, 'index'])
        ->middleware('can:reports.view')
        ->name('school.reports.index');

    Route::get('school/reports/students/{student}/terms/{term}', [ReportCardController::class, 'show'])
        ->middleware('can:reports.view')
        ->name('school.reports.show');

    Route::get('school/reports/students/{student}/terms/{term}/print', [ReportCardController::class, 'print'])
        ->middleware('can:reports.view')
        ->name('school.reports.print');

    Route::get('school/reports/students/{student}/terms/{term}/pdf', [ReportCardController::class, 'pdf'])
        ->middleware('can:reports.view')
        ->name('school.reports.pdf');

    Route::put('school/reports/students/{student}/terms/{term}/comment', [ReportCardController::class, 'saveComment'])
        ->middleware('can:reports.publish')
        ->name('school.reports.comment.update');

    Route::put('school/reports/students/{student}/terms/{term}/publish', [ReportCardController::class, 'publish'])
        ->middleware('can:reports.publish')
        ->name('school.reports.publish');

    Route::delete('school/reports/students/{student}/terms/{term}/publish', [ReportCardController::class, 'unpublish'])
        ->middleware('can:reports.publish')
        ->name('school.reports.unpublish');

    Route::get('school/announcements', [AnnouncementController::class, 'index'])
        ->middleware('can:announcements.view')
        ->name('school.announcements.index');

    Route::get('school/announcements/create', [AnnouncementController::class, 'create'])
        ->middleware('can:announcements.manage')
        ->name('school.announcements.create');

    Route::post('school/announcements', [AnnouncementController::class, 'store'])
        ->middleware('can:announcements.manage')
        ->name('school.announcements.store');

    Route::get('school/announcements/{announcement}', [AnnouncementController::class, 'show'])
        ->middleware('can:announcements.view')
        ->name('school.announcements.show');

    Route::get('school/announcements/{announcement}/edit', [AnnouncementController::class, 'edit'])
        ->middleware('can:announcements.manage')
        ->name('school.announcements.edit');

    Route::put('school/announcements/{announcement}', [AnnouncementController::class, 'update'])
        ->middleware('can:announcements.manage')
        ->name('school.announcements.update');

    Route::delete('school/announcements/{announcement}', [AnnouncementController::class, 'destroy'])
        ->middleware('can:announcements.manage')
        ->name('school.announcements.destroy');

    Route::put('school/announcements/{announcement}/publish', [AnnouncementController::class, 'publish'])
        ->middleware('can:announcements.manage')
        ->name('school.announcements.publish');

    Route::delete('school/announcements/{announcement}/publish', [AnnouncementController::class, 'unpublish'])
        ->middleware('can:announcements.manage')
        ->name('school.announcements.unpublish');

    Route::middleware(EnsureMessagingAccess::class)->group(function (): void {
        Route::get('messages', [MessageController::class, 'index'])
            ->name('messages.index');

        Route::get('messages/create', [MessageController::class, 'create'])
            ->name('messages.create');

        Route::post('messages', [MessageController::class, 'store'])
            ->name('messages.store');

        Route::get('messages/{user}', [MessageController::class, 'show'])
            ->name('messages.show');
    });

    Route::middleware(EnsurePortalUser::class)
        ->prefix('portal')
        ->name('portal.')
        ->group(function (): void {
            Route::get('/', [PortalController::class, 'index'])
                ->name('dashboard');

            Route::get('announcements', [PortalAnnouncementController::class, 'index'])
                ->name('announcements.index');

            Route::get('announcements/{announcement}', [PortalAnnouncementController::class, 'show'])
                ->name('announcements.show');

            Route::get('reports', [PortalReportCardController::class, 'index'])
                ->name('reports.index');

            Route::get('reports/students/{student}/terms/{term}', [PortalReportCardController::class, 'show'])
                ->name('reports.show');

            Route::get('reports/students/{student}/terms/{term}/print', [PortalReportCardController::class, 'print'])
                ->name('reports.print');

            Route::get('reports/students/{student}/terms/{term}/pdf', [PortalReportCardController::class, 'pdf'])
                ->name('reports.pdf');
        });
});

require __DIR__.'/settings.php';
