<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Incident\Domain\IncidentReport\Models\IncidentReport;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('IncidentReportPolicy', function () {
    test('allows admin-group and supervisors on viewAny, denies student', function () {
        foreach (['admin', 'teacher', 'supervisor'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);
            $this->actingAs($user);
            expect(Gate::allows('viewAny', IncidentReport::class))->toBeTrue();
        }

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('viewAny', IncidentReport::class))->toBeFalse();
    });

    test('allows admin and reporter owner on view, denies outsider', function () {
        $reporter = User::factory()->create();
        $reporter->assignRole('student');
        $report = IncidentReport::factory()->create(['reported_by' => $reporter->id]);
        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('view', $report))->toBeTrue();

        $this->actingAs($reporter);
        expect(Gate::allows('view', $report))->toBeTrue();

        $this->actingAs($outsider);
        expect(Gate::allows('view', $report))->toBeFalse();
    });

    test('allows any authenticated user to create', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('create', IncidentReport::class))->toBeTrue();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('create', IncidentReport::class))->toBeTrue();
    });

    test('reserves update and delete to admin', function () {
        $report = IncidentReport::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('update', $report))->toBeTrue()
            ->and(Gate::allows('delete', $report))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('update', $report))->toBeFalse()
            ->and(Gate::allows('delete', $report))->toBeFalse();
    });

    test('allows superadmin via before() bypass', function () {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $report = IncidentReport::factory()->create();

        $this->actingAs($superadmin);

        expect(Gate::allows('viewAny', IncidentReport::class))->toBeTrue()
            ->and(Gate::allows('delete', $report))->toBeTrue();
    });
});
