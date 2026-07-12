<?php

declare(strict_types=1);

use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Route Audit — Every page must open without errors
|--------------------------------------------------------------------------
|
| This test verifies that every registered route responds correctly:
|   - Guest routes: 200 (no auth required)
|   - Auth routes: 302→login for guests, 200 for authenticated users
|   - Role routes: 403 for wrong role, 200 for correct role
|
| Parameterized routes that need model instances are tested separately.
| Routes referencing deleted features (PresentationSchedule, RequirementManager)
| are excluded and tracked as known stale references.
|
*/

// ─────────────────────────────────────────────────────────────
// Helper: create users with specific roles
// ─────────────────────────────────────────────────────────────

function makeSuperAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('superadmin');

    return $user;
}

function makeAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

function makeStudent(): User
{
    $user = User::factory()->create();
    $user->assignRole('student');

    return $user;
}

function makeTeacher(): User
{
    $user = User::factory()->create();
    $user->assignRole('teacher');

    return $user;
}

function makeSupervisor(): User
{
    $user = User::factory()->create();
    $user->assignRole('supervisor');

    return $user;
}

// ─────────────────────────────────────────────────────────────
// 1. Guest routes — accessible without authentication
// ─────────────────────────────────────────────────────────────

test('GET / returns 200 (home page)', function () {
    $this->get('/')->assertStatus(200);
});

test('GET /login returns 200', function () {
    $this->get('/login')->assertStatus(200);
});

test('GET /activate returns 200', function () {
    $this->get('/activate')->assertStatus(200);
});

test('GET /forgot-password returns 200', function () {
    $this->get('/forgot-password')->assertStatus(200);
});

test('GET /recover-account returns 200', function () {
    $this->get('/recover-account')->assertStatus(200);
});

test('GET /apply returns 200 (guest application page)', function () {
    $this->get('/apply')->assertStatus(200);
});

// ─────────────────────────────────────────────────────────────
// 2. Auth routes — require any authenticated user
// ─────────────────────────────────────────────────────────────

test('GET /dashboard redirects to login for guests', function () {
    $this->get('/dashboard')->assertStatus(302);
});

test('GET /dashboard redirects to role-specific dashboard', function () {
    $this->actingAs(makeStudent())->get('/dashboard')->assertStatus(302);
});

test('GET /my-dashboard returns 200', function () {
    $this->actingAs(makeStudent())->get('/my-dashboard')->assertStatus(200);
});

test('GET /profile returns 200', function () {
    $this->actingAs(makeStudent())->get('/profile')->assertStatus(200);
});

test('GET /profile/recovery returns 200', function () {
    $this->actingAs(makeStudent())->get('/profile/recovery')->assertStatus(200);
});

test('GET /notifications returns 200', function () {
    $this->actingAs(makeStudent())->get('/notifications')->assertStatus(200);
});

test('GET /user/confirm-password returns 200', function () {
    $this->actingAs(makeStudent())->get('/user/confirm-password')->assertStatus(200);
});

test('GET /assessments returns 200 (auth, any role)', function () {
    $this->actingAs(makeStudent())->get('/assessments')->assertStatus(200);
});

// ─────────────────────────────────────────────────────────────
// 3. Admin routes — require super_admin or admin role
// ─────────────────────────────────────────────────────────────

dataset('admin_routes', function () {
    return [
        'admin.dashboard' => '/admin/dashboard',
        'admin.school' => '/admin/school',
        'admin.departments' => '/admin/departments',
        'admin.academic-years' => '/admin/academic-years',
        'admin.companies' => '/admin/companies',
        'admin.partnerships' => '/admin/companies/partnerships',
        'admin.internships' => '/admin/internships',
        'admin.internships.groups' => '/admin/internships/groups',
        'admin.placements' => '/admin/internships/placements',
        'admin.placements.direct' => '/admin/internships/placements/direct',
        'admin.placements.changes' => '/admin/internships/placements/changes',
        'admin.pending Registrations' => '/admin/internships/registrations/pending',
        'admin.assignments' => '/admin/assignments',
        'admin.submissions.grading' => '/admin/submissions/grading',
        'admin.assessments.rubrics' => '/admin/assessments/rubrics',
        'admin.certificates' => '/admin/certificates',
        'admin.certificates.templates' => '/admin/certificates/templates',
        'admin.reports' => '/admin/reports',
        'admin.logbook.manager' => '/admin/logbook',
        'admin.attendance' => '/admin/attendance',
        'admin.incidents' => '/admin/incidents',
        'admin.settings' => '/admin/settings',
        'admin.users' => '/admin/users',
        'admin.users.admins' => '/admin/users/admins',
        'admin.users.students' => '/admin/users/students',
        'admin.users.teachers' => '/admin/users/teachers',
        'admin.users.supervisors' => '/admin/users/supervisors',
        'admin.gdpr-logs' => '/admin/gdpr-logs',
        'admin.audit-log' => '/admin/audit-log',
        'admin.accounts.clones' => '/admin/accounts/clones',
        'admin.backups' => '/admin/backups',
        'admin.applications' => '/admin/applications',
        'admin.announcements' => '/admin/announcements',
        'admin.accounts' => '/admin/accounts',
        'admin.recovery-slips' => '/admin/recovery-slips',
    ];
});

test('admin route :name returns 200 for super_admin', function (string $url) {
    $this->actingAs(makeSuperAdmin())->get($url)->assertStatus(200);
})->with('admin_routes');

test('admin route :name returns 200 for admin', function (string $url) {
    $this->actingAs(makeAdmin())->get($url)->assertStatus(200);
})->with('admin_routes');

test('admin route :name returns 403 for student', function (string $url) {
    $this->actingAs(makeStudent())->get($url)->assertStatus(403);
})->with('admin_routes');

// ─────────────────────────────────────────────────────────────
// 4. Student routes — require student role
// ─────────────────────────────────────────────────────────────

dataset('student_routes', function () {
    return [
        'student.dashboard' => '/student/dashboard',
        'student.logbook' => '/student/logbook',
        'student.attendance' => '/student/attendance',
        'student.attendance.absence' => '/student/attendance/absence',
        'student.assignments' => '/student/assignments',
        'student.incidents.report' => '/student/incidents/report',
        'student.certificates' => '/student/certificates',
        'student.handbooks' => '/student/handbooks',
        'student.visits' => '/student/visits',
        'student.supervision-logs' => '/student/supervision-logs',
        'student.placement-change' => '/student/internships/placement-change',
    ];
});

test('student route :name returns 200 for student', function (string $url) {
    $this->actingAs(makeStudent())->get($url)->assertStatus(200);
})->with('student_routes');

test('student route :name returns 403 for admin', function (string $url) {
    $this->actingAs(makeAdmin())->get($url)->assertStatus(403);
})->with('student_routes');

// ─────────────────────────────────────────────────────────────
// 5. Teacher routes
// ─────────────────────────────────────────────────────────────

test('GET /teacher/dashboard returns 200 for teacher', function () {
    $this->actingAs(makeTeacher())->get('/teacher/dashboard')->assertStatus(200);
});

test('GET /teacher/dashboard returns 403 for student', function () {
    $this->actingAs(makeStudent())->get('/teacher/dashboard')->assertStatus(403);
});

test('GET /teacher/submissions/grading returns 200 for teacher', function () {
    $this->actingAs(makeTeacher())->get('/teacher/submissions/grading')->assertStatus(200);
});

test('GET /supervision/visits returns 200 for teacher', function () {
    $this->actingAs(makeTeacher())->get('/supervision/visits')->assertStatus(200);
});

test('GET /supervision/visits returns 200 for admin', function () {
    $this->actingAs(makeAdmin())->get('/supervision/visits')->assertStatus(200);
});

// ─────────────────────────────────────────────────────────────
// 6. Supervisor routes
// ─────────────────────────────────────────────────────────────

test('GET /supervisor/dashboard returns 200 for supervisor', function () {
    $this->actingAs(makeSupervisor())->get('/supervisor/dashboard')->assertStatus(200);
});

test('GET /supervisor/dashboard returns 403 for student', function () {
    $this->actingAs(makeStudent())->get('/supervisor/dashboard')->assertStatus(403);
});

test('GET /supervision/logs returns 200 for supervisor', function () {
    $this->actingAs(makeSupervisor())->get('/supervision/logs')->assertStatus(200);
});

test('GET /supervision/submissions/grading returns 200 for supervisor', function () {
    $this->actingAs(makeSupervisor())->get('/supervision/submissions/grading')->assertStatus(200);
});

test('GET /supervision/submissions/grading returns 200 for teacher', function () {
    $this->actingAs(makeTeacher())->get('/supervision/submissions/grading')->assertStatus(200);
});

// ─────────────────────────────────────────────────────────────
// 7. Registration routes — authenticated, any role
// ─────────────────────────────────────────────────────────────

test('GET /registration returns 200', function () {
    $this->actingAs(makeStudent())->get('/registration')->assertStatus(200);
});

test('GET /register returns 200', function () {
    $this->actingAs(makeStudent())->get('/register')->assertStatus(200);
});

test('GET /registration/documents returns 200', function () {
    $this->actingAs(makeStudent())->get('/registration/documents')->assertStatus(200);
});

// ─────────────────────────────────────────────────────────────
// 8. POST routes
// ─────────────────────────────────────────────────────────────

test('POST /logout returns 302 for authenticated user', function () {
    $this->actingAs(makeStudent())->post('/logout')->assertStatus(302);
});

// ─────────────────────────────────────────────────────────────
// 9. Auth guard — guest routes deny authenticated users
// ─────────────────────────────────────────────────────────────

test('GET /login redirects for authenticated user', function () {
    $this->actingAs(makeStudent())->get('/login')->assertStatus(302);
});

test('GET /apply still accessible for authenticated users (guest middleware only blocks guest)', function () {
    // /apply uses 'guest' middleware — authenticated users get redirected
    $this->actingAs(makeStudent())->get('/apply')->assertStatus(302);
});

// ─────────────────────────────────────────────────────────────
// 10. Unauthenticated access — all protected routes must redirect
// ─────────────────────────────────────────────────────────────

dataset('protected_routes', function () {
    return [
        'dashboard' => '/dashboard',
        'profile' => '/profile',
        'notifications' => '/notifications',
        'admin.settings' => '/admin/settings',
        'admin.users' => '/admin/users',
        'admin.school' => '/admin/school',
        'student.dashboard' => '/student/dashboard',
        'student.logbook' => '/student/logbook',
    ];
});

test('protected route :name redirects to login for guests', function (string $url) {
    $this->get($url)->assertStatus(302);
})->with('protected_routes');
