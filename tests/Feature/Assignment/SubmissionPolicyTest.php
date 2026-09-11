<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Assignment\Domain\Submission\Models\Submission;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('T657Z: SubmissionPolicy', function () {
    test('T657Z-FR-SUBM-012: allows mentor roles on viewAny, denies student', function () {
        $submission = Submission::factory()->create();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('viewAny', Submission::class))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('viewAny', Submission::class))->toBeFalse();
    });

    test('T657Z-FR-SUBM-012: allows admin and owning student on view, denies outsider', function () {
        $owner = User::factory()->create();
        $owner->assignRole('student');
        $submission = Submission::factory()->create(['student_id' => $owner->id]);
        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('view', $submission))->toBeTrue();

        $this->actingAs($owner);
        expect(Gate::allows('view', $submission))->toBeTrue();

        $this->actingAs($outsider);
        expect(Gate::allows('view', $submission))->toBeFalse();
    });

    test('T657Z-FR-SUBM-012: student-only create', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('create', Submission::class))->toBeTrue();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('create', Submission::class))->toBeFalse();
    });

    test('T657Z-FR-SUBM-012: owner-only update while submitted', function () {
        $owner = User::factory()->create();
        $owner->assignRole('student');
        $submitted = Submission::factory()->create(['student_id' => $owner->id, 'status' => 'submitted']);
        $draft = Submission::factory()->create(['student_id' => $owner->id, 'status' => 'draft', 'submitted_at' => null]);
        $other = User::factory()->create();
        $other->assignRole('student');

        $this->actingAs($owner);
        expect(Gate::allows('update', $submitted))->toBeTrue()
            ->and(Gate::allows('update', $draft))->toBeFalse();

        $this->actingAs($other);
        expect(Gate::allows('update', $submitted))->toBeFalse();
    });

    test('T657Z-FR-SUBM-012: allows admin on verify and delete, denies student', function () {
        $submission = Submission::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('verify', $submission))->toBeTrue()
            ->and(Gate::allows('delete', $submission))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('delete', $submission))->toBeFalse();
    });
});
