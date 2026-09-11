<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Assignment\Domain\Submission\Models\Submission;
use App\Modules\Assignment\Models\Assignment;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('AssignmentPolicy', function () {
    test('allows every role on viewAny and view', function () {
        $assignment = Assignment::factory()->create();

        foreach (['admin', 'teacher', 'supervisor', 'student'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);
            $this->actingAs($user);

            expect(Gate::allows('viewAny', Assignment::class))->toBeTrue()
                ->and(Gate::allows('view', $assignment))->toBeTrue();
        }
    });

    test('allows teacher on create, update, and publish; denies student', function () {
        $assignment = Assignment::factory()->create();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('create', Assignment::class))->toBeTrue()
            ->and(Gate::allows('update', $assignment))->toBeTrue()
            ->and(Gate::allows('publish', $assignment))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('create', Assignment::class))->toBeFalse()
            ->and(Gate::allows('update', $assignment))->toBeFalse()
            ->and(Gate::allows('publish', $assignment))->toBeFalse();
    });

    test('allows admin to delete an assignment without submissions', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $assignment = Assignment::factory()->create();

        $this->actingAs($admin);

        expect(Gate::allows('delete', $assignment))->toBeTrue();
    });

    test('denies admin delete when submissions exist and denies teacher delete', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $assignment = Assignment::factory()->create();
        Submission::factory()->create([
            'assignment_id' => $assignment->id,
        ]);

        $this->actingAs($admin);
        expect(Gate::allows('delete', $assignment))->toBeFalse();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('delete', $assignment))->toBeFalse();
    });

    test('allows superadmin via before() bypass', function () {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $assignment = Assignment::factory()->create();

        $this->actingAs($superadmin);

        expect(Gate::allows('create', Assignment::class))->toBeTrue()
            ->and(Gate::allows('publish', $assignment))->toBeTrue();
    });
});
