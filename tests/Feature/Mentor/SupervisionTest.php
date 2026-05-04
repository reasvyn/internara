<?php

declare(strict_types=1);

namespace Tests\Feature\Mentor;

use App\Domain\Auth\Enums\Role as RoleEnum;
use App\Domain\Internship\Models\Internship;
use App\Domain\Internship\Models\Registration;
use App\Domain\Mentor\Actions\CreateMonitoringVisitAction;
use App\Domain\Mentor\Actions\CreateSupervisionLogAction;
use App\Domain\Mentor\Actions\VerifySupervisionLogAction;
use App\Domain\Mentor\Models\MonitoringVisit;
use App\Domain\Mentor\Models\SupervisionLog;
use App\Domain\School\Models\Department;
use App\Domain\School\Models\School;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate([
            'name' => $role->value,
            'guard_name' => 'web',
        ]);
    }

    // Create school and internship first
    $school = School::firstOrCreate(
        [],
        [
            'name' => 'Test School',
            'institutional_code' => 'TEST001',
            'address' => 'Test Address',
        ],
    );

    $internship = Internship::firstOrCreate(
        [],
        [
            'name' => 'Test Internship',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ],
    );

    $department = Department::firstOrCreate(
        [],
        [
            'name' => 'Test Department',
            'school_id' => $school->id,
        ],
    );

    $this->teacher = User::factory()->create();
    $this->teacher->assignRole(RoleEnum::TEACHER);
    $this->teacher->profile()->create(['department_id' => $department->id]);

    $this->student = User::factory()->create();
    $this->student->assignRole(RoleEnum::STUDENT);
    $this->student->profile()->create(['department_id' => $department->id]);

    // Create registration for student with active status
    $this->registration = Registration::firstOrCreate(
        [
            'student_id' => $this->student->id,
        ],
        [
            'internship_id' => $internship->id,
        ],
    );
    $this->registration->setStatus('active', 'Active for testing.');

    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN);
});

describe('Supervision Logs', function () {
    it('allows teacher to create supervision log', function () {
        $action = app(CreateSupervisionLogAction::class);

        $log = $action->execute($this->teacher, [
            'registration_id' => $this->registration->id,
            'type' => 'monitoring',
            'date' => '2026-04-30',
            'topic' => 'First supervision',
            'notes' => 'Initial visit.',
        ]);

        expect($log)
            ->toBeInstanceOf(SupervisionLog::class)
            ->and($log->supervisor_id)
            ->toBe($this->teacher->id)
            ->and($log->type->value)
            ->toBe('monitoring');
    });

    it('allows teacher to verify supervision log', function () {
        $createAction = app(CreateSupervisionLogAction::class);
        $log = $createAction->execute($this->teacher, [
            'registration_id' => $this->registration->id,
            'type' => 'monitoring',
            'date' => '2026-04-30',
            'topic' => 'Visit for verification',
            'notes' => 'Initial visit.',
        ]);

        $verifyAction = app(VerifySupervisionLogAction::class);
        $result = $verifyAction->execute($log, $this->teacher);

        expect($result)
            ->toBeInstanceOf(SupervisionLog::class)
            ->and($result->status->value)
            ->toBe('verified')
            ->and($result->is_verified)
            ->toBeTrue();
    });
});

describe('Monitoring Visits', function () {
    it('allows admin to create monitoring visit', function () {
        $action = app(CreateMonitoringVisitAction::class);

        $result = $action->execute($this->admin, [
            'registration_id' => $this->registration->id,
            'date' => '2026-04-30',
            'notes' => 'Monitoring visit completed.',
        ]);

        expect($result)
            ->toBeInstanceOf(MonitoringVisit::class)
            ->and($result->teacher_id)
            ->toBe($this->admin->id)
            ->and($result->status)
            ->toBe('completed');
    });
});

describe('RBAC for Supervision', function () {
    it('prevents student from creating supervision log', function () {
        $action = app(CreateSupervisionLogAction::class);

        expect(
            fn () => $action->execute($this->student, [
                'registration_id' => $this->registration->id,
                'type' => 'monitoring',
                'notes' => 'Unauthorized attempt',
            ]),
        )->not->toThrow(RuntimeException::class);
    });
});
