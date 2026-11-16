<?php

/*
|--------------------------------------------------------------------------
| Application permissions
|--------------------------------------------------------------------------
|
| Every permission in the system, grouped into the module it belongs to.
| The PermissionSeeder creates these and grants them to the Administrator
| role. Permissions are named "<module>.<action>" and checked through
| Laravel's Gate.
|
*/

return [
    [
        'name' => 'School administration',
        'description' => 'School-wide configuration, user accounts and access control.',
        'permissions' => [
            'settings.manage' => 'Manage school settings',
            'roles.manage' => 'Manage roles and permissions',
            'users.view' => 'View user accounts',
            'users.manage' => 'Create and edit user accounts',
        ],
    ],
    [
        'name' => 'People',
        'description' => 'Students, staff and family records.',
        'permissions' => [
            'people.students.view' => 'View students',
            'people.students.manage' => 'Add and edit students',
            'people.staff.view' => 'View staff',
            'people.staff.manage' => 'Add and edit staff',
            'people.families.view' => 'View families',
            'people.families.manage' => 'Add and edit families',
        ],
    ],
    [
        'name' => 'Curriculum',
        'description' => 'Courses, classes and enrolment.',
        'permissions' => [
            'curriculum.view' => 'View courses and classes',
            'curriculum.manage' => 'Manage courses and classes',
            'curriculum.enrolment.manage' => 'Manage class enrolment',
        ],
    ],
    [
        'name' => 'Timetable',
        'description' => 'Class timetables.',
        'permissions' => [
            'timetable.view' => 'View timetables',
            'timetable.manage' => 'Manage timetable entries',
        ],
    ],
    [
        'name' => 'Attendance',
        'description' => 'Attendance registers and records.',
        'permissions' => [
            'attendance.view' => 'View attendance records',
            'attendance.record' => 'Record attendance',
        ],
    ],
    [
        'name' => 'Assessment',
        'description' => 'Marks, grades and report cards.',
        'permissions' => [
            'assessment.view' => 'View marks and grades',
            'assessment.manage' => 'Enter and edit marks',
            'reports.view' => 'View report cards',
            'reports.publish' => 'Generate and publish report cards',
        ],
    ],
    [
        'name' => 'Communication',
        'description' => 'Announcements and messages.',
        'permissions' => [
            'announcements.view' => 'View announcements',
            'announcements.manage' => 'Manage announcements',
            'messages.send' => 'Send messages',
        ],
    ],
];
