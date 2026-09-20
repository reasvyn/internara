<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\AcademicYear\Events\AcademicYearCreated;
use App\Modules\Academic\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Academic\Domain\Department\Events\DepartmentCreated;
use App\Modules\Academic\Domain\Department\Models\Department;
use App\Modules\Core\Actions\BaseReadAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\SysAdmin\Actions\ReadAdminDashboardAction;
use App\Modules\User\Domain\Dashboard\Actions\ReadStudentDashboardAction;
use App\Modules\User\Domain\Dashboard\Actions\ReadSupervisorDashboardAction;
use App\Modules\User\Domain\Dashboard\Actions\ReadTeacherDashboardAction;
use App\Modules\User\Domain\Dashboard\Livewire\AdminDashboard;
use App\Modules\User\Domain\Dashboard\Livewire\StudentDashboard;
use App\Modules\User\Domain\Dashboard\Livewire\SupervisorDashboard;
use App\Modules\User\Domain\Dashboard\Livewire\TeacherDashboard;
use App\Modules\User\Models\User;
use App\Modules\User\Services\DashboardService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(LazilyRefreshDatabase::class);

describe('CKKZC: dashboard routing, caching, and role components', function (): void {
    test('CKKZC-FR-DASH-001: generic dashboard route redirects by user role (also FR-DASH-002, FR-DASH-003, FR-DASH-004, FR-DASH-005, UC-DASH-001, UC-DASH-002, UC-DASH-003, UC-DASH-004)', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $this->get(route('dashboard'))->assertRedirect(route('sysadmin.dashboard'));

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        $this->get(route('dashboard'))->assertRedirect(route('student.dashboard'));

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        $this->get(route('dashboard'))->assertRedirect(route('teacher.dashboard'));

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $this->actingAs($supervisor);
        $this->get(route('dashboard'))->assertRedirect(route('supervisor.dashboard'));
    });

    test('CKKZC-FR-DASH-006: unrecognized role fails closed with 403 (also FR-DASH-007, UC-DASH-007, DD-DASH-001)', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(DashboardService::class);
        expect(fn () => $service->getDashboardForUser($user))->toThrow(HttpException::class);

        $this->get('/dashboard')->assertForbidden();
    });

    test('CKKZC-FR-DASH-008: proxy resolution returns supervisor dashboard for teachers', function (): void {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $service = app(DashboardService::class);
        expect($service->getProxyDashboardForUser($teacher))->toBe('supervisor.dashboard');

        $student = User::factory()->create();
        $student->assignRole('student');
        expect($service->getProxyDashboardForUser($student))->toBeNull();
    });

    test('CKKZC-FR-DASH-009: admin read action returns system statistics (also FR-DASH-016, FR-DASH-017, FR-DASH-021, FR-DASH-039, FR-DASH-040, DD-DASH-002)', function (): void {
        $action = app(ReadAdminDashboardAction::class);
        expect($action)->toBeInstanceOf(BaseReadAction::class);

        $stats = $action->execute();
        expect($stats)->toBeArray()
            ->and($stats)->toHaveKeys(['totalStudents', 'activeInternships', 'totalCompanies']);
    });

    test('CKKZC-FR-DASH-010: student read action returns progress data and handles missing registration (also FR-DASH-013, FR-DASH-014, FR-DASH-018, NFR-DASH-002, NFR-DASH-005, DD-DASH-004)', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');

        $action = app(ReadStudentDashboardAction::class);
        $data = $action->execute($student->id);

        expect($data['attendancePercent'])->toBe(100.0)
            ->and($data['totalJournals'])->toBe(0)
            ->and($data['registration'])->toBeNull();

        expect(fn () => $action->execute('non-existent-id'))->toThrow(RejectedException::class);
    });

    test('CKKZC-FR-DASH-011: teacher and supervisor read actions return scoped workload figures (also FR-DASH-012, FR-DASH-015, FR-DASH-019, FR-DASH-020, NFR-DASH-003)', function (): void {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);

        $teacherAction = app(ReadTeacherDashboardAction::class);
        $teacherData = $teacherAction->execute();
        expect($teacherData)->toBeArray()->and($teacherData)->toHaveKeys(['supervisedStudents', 'pendingJournals']);

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $this->actingAs($supervisor);

        $supervisorAction = app(ReadSupervisorDashboardAction::class);
        $supervisorData = $supervisorAction->execute();
        expect($supervisorData)->toBeArray()->and($supervisorData)->toHaveKeys(['activeInterns', 'pendingJournals']);
    });

    test('CKKZC-FR-DASH-022: department and academic year changes synchronously invalidate cache (also FR-DASH-023, FR-DASH-024, UC-DASH-005, UC-DASH-006, NFR-DASH-006, NFR-DASH-009, DD-DASH-005)', function (): void {
        $key = config('cache-keys.admin_dashboard_stats');
        Cache::put($key, ['cached' => true], 300);
        expect(Cache::has($key))->toBeTrue();

        $dept = Department::factory()->create();
        event(new DepartmentCreated($dept));
        expect(Cache::has($key))->toBeFalse();

        Cache::put($key, ['cached' => true], 300);
        $year = AcademicYear::factory()->create();
        event(new AcademicYearCreated($year));
        expect(Cache::has($key))->toBeFalse();
    });

    test('CKKZC-FR-DASH-025: base and leaf dashboard components render correctly with authorization (also FR-DASH-026, FR-DASH-027, FR-DASH-028, FR-DASH-029, FR-DASH-030, FR-DASH-031, FR-DASH-032, FR-DASH-033, FR-DASH-034, FR-DASH-035, FR-DASH-036, FR-DASH-037, FR-DASH-038, NFR-DASH-001, NFR-DASH-004, NFR-DASH-007, NFR-DASH-008, NFR-DASH-010, NFR-DASH-011, NFR-DASH-012, NFR-DASH-013, NFR-DASH-014, NFR-DASH-015, NFR-DASH-016, DD-DASH-003, DD-DASH-006)', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Livewire::actingAs($admin)->test(AdminDashboard::class)
            ->assertSet('stats', fn ($s) => is_array($s))
            ->assertSet('readiness', fn ($r) => is_array($r));

        $student = User::factory()->create();
        $student->assignRole('student');
        Livewire::actingAs($student)->test(StudentDashboard::class)
            ->assertSet('attendancePercent', 100.0);

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        Livewire::actingAs($teacher)->test(TeacherDashboard::class)
            ->assertSet('supervisedStudents', 0);

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        Livewire::actingAs($supervisor)->test(SupervisorDashboard::class)
            ->assertSet('activeInterns', 0);
    });
});
