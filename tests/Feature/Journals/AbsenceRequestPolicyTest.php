<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Journals\Domain\AbsenceRequest\Models\AbsenceRequest;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('AbsenceRequestPolicy', function () {
    test('allows every role on viewAny', function () {
        foreach (['admin', 'teacher', 'supervisor', 'student'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);
            $this->actingAs($user);
            expect(Gate::allows('viewAny', AbsenceRequest::class))->toBeTrue();
        }
    });

    test('allows admin and owning student on view, denies outsider', function () {
        $owner = User::factory()->create();
        $owner->assignRole('student');
        $absence = AbsenceRequest::factory()->create(['user_id' => $owner->id]);
        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('view', $absence))->toBeTrue();

        $this->actingAs($owner);
        expect(Gate::allows('view', $absence))->toBeTrue();

        $this->actingAs($outsider);
        expect(Gate::allows('view', $absence))->toBeFalse();
    });

    test('denies unassigned teacher on view without a mentor link', function () {
        $owner = User::factory()->create();
        $owner->assignRole('student');
        $absence = AbsenceRequest::factory()->create(['user_id' => $owner->id]);
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $this->actingAs($teacher);

        expect(Gate::allows('view', $absence))->toBeFalse();
    });

    test('student-only create', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('create', AbsenceRequest::class))->toBeTrue();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('create', AbsenceRequest::class))->toBeFalse();
    });

    test('reserves update and delete to admin', function () {
        $absence = AbsenceRequest::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('update', $absence))->toBeTrue()
            ->and(Gate::allows('delete', $absence))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('update', $absence))->toBeFalse()
            ->and(Gate::allows('delete', $absence))->toBeFalse();
    });
});
