<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Enrollment\Domain\Placement\Models\PlacementChangeRequest;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('J9GBH: PlacementChangeRequestPolicy', function () {
    test('J9GBH-FR-PLACE-021: allows admin-group, teacher, and student on viewAny; denies supervisor', function () {
        foreach (['admin', 'teacher', 'student'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);
            $this->actingAs($user);
            expect(Gate::allows('viewAny', PlacementChangeRequest::class))->toBeTrue();
        }

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $this->actingAs($supervisor);
        expect(Gate::allows('viewAny', PlacementChangeRequest::class))->toBeFalse();
    });

    test('J9GBH-FR-PLACE-021: allows admin and requesting owner on view, denies outsider', function () {
        $requester = User::factory()->create();
        $requester->assignRole('student');
        $request = PlacementChangeRequest::factory()->create(['requested_by' => $requester->id]);
        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('view', $request))->toBeTrue();

        $this->actingAs($requester);
        expect(Gate::allows('view', $request))->toBeTrue();

        $this->actingAs($outsider);
        expect(Gate::allows('view', $request))->toBeFalse();
    });

    test('J9GBH-FR-PLACE-021: student-only create', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('create', PlacementChangeRequest::class))->toBeTrue();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('create', PlacementChangeRequest::class))->toBeFalse();
    });

    test('J9GBH-FR-PLACE-021: update and delete reserved to admin', function () {
        $request = PlacementChangeRequest::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('update', $request))->toBeTrue()
            ->and(Gate::allows('delete', $request))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('update', $request))->toBeFalse()
            ->and(Gate::allows('delete', $request))->toBeFalse();
    });
});
