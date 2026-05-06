<?php

declare(strict_types=1);

return [
    'role' => [
        'super_admin' => 'Super Administrator',
        'admin' => 'Administrator',
        'teacher' => 'Teacher',
        'student' => 'Student',
        'supervisor' => 'Supervisor',
    ],

    'permission' => [
        // User management
        'users.view' => 'View Users',
        'users.create' => 'Create Users',
        'users.edit' => 'Edit Users',
        'users.delete' => 'Delete Users',

        // Student management
        'students.view' => 'View Students',
        'students.create' => 'Create Students',
        'students.edit' => 'Edit Students',
        'students.delete' => 'Delete Students',

        // Teacher management
        'teachers.view' => 'View Teachers',
        'teachers.create' => 'Create Teachers',
        'teachers.edit' => 'Edit Teachers',
        'teachers.delete' => 'Delete Teachers',

        // Supervisor management
        'supervisors.view' => 'View Supervisors',
        'supervisors.create' => 'Create Supervisors',
        'supervisors.edit' => 'Edit Supervisors',
        'supervisors.delete' => 'Delete Supervisors',

        // Company management
        'companies.view' => 'View Companies',
        'companies.create' => 'Create Companies',
        'companies.edit' => 'Edit Companies',
        'companies.delete' => 'Delete Companies',

        // Department management
        'departments.view' => 'View Departments',
        'departments.create' => 'Create Departments',
        'departments.edit' => 'Edit Departments',
        'departments.delete' => 'Delete Departments',

        // School management
        'schools.view' => 'View Schools',
        'schools.edit' => 'Edit Schools',

        // Internship management
        'internships.view' => 'View Internships',
        'internships.create' => 'Create Internships',
        'internships.edit' => 'Edit Internships',
        'internships.delete' => 'Delete Internships',
        'internships.manage' => 'Manage Internships',

        // Assignment management
        'assignments.view' => 'View Assignments',
        'assignments.create' => 'Create Assignments',
        'assignments.edit' => 'Edit Assignments',
        'assignments.delete' => 'Delete Assignments',
        'assignments.grade' => 'Grade Assignments',

        // Assessment management
        'assessments.view' => 'View Assessments',
        'assessments.create' => 'Create Assessments',
        'assessments.edit' => 'Edit Assessments',
        'assessments.delete' => 'Delete Assessments',
        'assessments.grade' => 'Grade Assessments',

        // Attendance management
        'attendance.view' => 'View Attendance',
        'attendance.clockin' => 'Clock In',
        'attendance.clockout' => 'Clock Out',
        'attendance.manage' => 'Manage Attendance',

        // Journal management
        'journals.view' => 'View Journals',
        'journals.create' => 'Create Journals',
        'journals.edit' => 'Edit Journals',
        'journals.verify' => 'Verify Journals',

        // Report management
        'reports.view' => 'View Reports',
        'reports.create' => 'Create Reports',
        'reports.export' => 'Export Reports',

        // Notification management
        'notifications.view' => 'View Notifications',
        'notifications.manage' => 'Manage Notifications',

        // System settings
        'settings.view' => 'View Settings',
        'settings.edit' => 'Edit Settings',

        // Audit & Logs
        'audit.view' => 'View Audit Logs',
        'audit.export' => 'Export Audit Logs',

        // Dashboard access
        'dashboard.student' => 'Student Dashboard',
        'dashboard.teacher' => 'Teacher Dashboard',
        'dashboard.mentor' => 'Mentor Dashboard',
        'dashboard.admin' => 'Admin Dashboard',
        'dashboard.supervisor' => 'Supervisor Dashboard',
    ],
];
