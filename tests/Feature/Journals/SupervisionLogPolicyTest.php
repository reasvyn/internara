<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journals\Domain\SupervisionLog\Models\SupervisionLog;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('SupervisionLogPolicy', function () {
    test('allows admin, supervisor, and student on viewAny; denies teacher', function () {
        foreach (['admin', 'supervisor', 'student'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);
            $this->actingAs($user);
            expect(Gate::allows('viewAny', SupervisionLog::class))->toBeTrue();
        }

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('viewAny', SupervisionLog::class))->toBeFalse();
    });

    test('allows registration student and assigned supervisor on view, denies outsider', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $registration = Registration::factory()->create(['student_id' => $student->id]);
        $log = SupervisionLog::factory()->create([
            'registration_id' => $registration->id,
            'supervisor_id' => $supervisor->id,
        ]);
        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        $this->actingAs($student);
        expect(Gate::allows('view', $log))->toBeTrue();

        $this->actingAs($supervisor);
        expect(Gate::allows('view', $log))->toBeTrue();

        $this->actingAs($outsider);
        expect(Gate::allows('view', $log))->toBeFalse();
    });

    test('student-only create', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('create', SupervisionLog::class))->toBeTrue();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('create', SupervisionLog::class))->toBeFalse();
    });

    test('allows owning student to update a draft but not a submitted log, and never admin', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $registration = Registration::factory()->create(['student_id' => $student->id]);
        $draft = SupervisionLog::factory()->create([
            'registration_id' => $registration->id,
            'status' => 'draft',
        ]);
        $submitted = SupervisionLog::factory()->create([
            'registration_id' => $registration->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($student);
        expect(Gate::allows('update', $draft))->toBeTrue()
            ->and(Gate::allows('update', $submitted))->toBeFalse();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('update', $draft))->toBeFalse();
    });

    test('allows assigned supervisor with supervisor role on review, denies outsider', function () {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $registration = Registration::factory()->create();
        $log = SupervisionLog::factory()->create([
            'registration_id' => $registration->id,
            'supervisor_id' => $supervisor->id,
        ]);
        $outsider = User::factory()->create();
        $outsider->assignRole('supervisor');

        $this->actingAs($supervisor);
        expect(Gate::allows('review', $log))->toBeTrue();

        $this->actingAs($outsider);
        expect(Gate::allows('review', $log))->toBeFalse();
    });

    test('allows admin and owning student on delete of a draft, denies outsider', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $registration = Registration::factory()->create(['student_id' => $student->id]);
        $log = SupervisionLog::factory()->create([
            'registration_id' => $registration->id,
            'status' => 'draft',
        ]);
        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('delete', $log))->toBeTrue();

        $this->actingAs($student);
        expect(Gate::allows('delete', $log))->toBeTrue();

        $this->actingAs($outsider);
        expect(Gate::allows('delete', $log))->toBeFalse();
    });
});
