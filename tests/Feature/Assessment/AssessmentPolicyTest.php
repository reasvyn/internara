<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Assessment\Models\Assessment;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('AssessmentPolicy', function () {
    test('allows admin-group and teacher on viewAny and create, denies student', function () {
        $assessment = Assessment::factory()->create();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('viewAny', Assessment::class))->toBeTrue()
            ->and(Gate::allows('create', Assessment::class))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('viewAny', Assessment::class))->toBeFalse()
            ->and(Gate::allows('create', Assessment::class))->toBeFalse();
    });

    test('allows admin, evaluator owner, and registration student on view; denies outsider', function () {
        $evaluator = User::factory()->create();
        $evaluator->assignRole('teacher');
        $student = User::factory()->create();
        $student->assignRole('student');
        $registration = Registration::factory()->create(['student_id' => $student->id]);
        $assessment = Assessment::factory()->create([
            'evaluator_id' => $evaluator->id,
            'registration_id' => $registration->id,
        ]);
        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('view', $assessment))->toBeTrue();

        $this->actingAs($evaluator);
        expect(Gate::allows('view', $assessment))->toBeTrue();

        $this->actingAs($student);
        expect(Gate::allows('view', $assessment))->toBeTrue();

        $this->actingAs($outsider);
        expect(Gate::allows('view', $assessment))->toBeFalse();
    });

    test('allows evaluator owner to update a non-finalized assessment, denies when finalized', function () {
        $evaluator = User::factory()->create();
        $evaluator->assignRole('teacher');
        $open = Assessment::factory()->create(['evaluator_id' => $evaluator->id]);
        $closed = Assessment::factory()->create(['evaluator_id' => $evaluator->id]);
        $closed->forceFill(['finalized_at' => now()])->save();

        $this->actingAs($evaluator);

        expect(Gate::allows('update', $open))->toBeTrue()
            ->and(Gate::allows('update', $closed))->toBeFalse();
    });

    test('denies non-evaluator student on update', function () {
        $evaluator = User::factory()->create();
        $evaluator->assignRole('teacher');
        $assessment = Assessment::factory()->create(['evaluator_id' => $evaluator->id]);
        $student = User::factory()->create();
        $student->assignRole('student');

        $this->actingAs($student);

        expect(Gate::allows('update', $assessment))->toBeFalse();
    });

    test('allows teacher on finalize, denies student', function () {
        $assessment = Assessment::factory()->create();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('finalize', $assessment))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('finalize', $assessment))->toBeFalse();
    });

    test('allows admin to delete a non-finalized assessment, denies when finalized or non-admin', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $open = Assessment::factory()->create();
        $closed = Assessment::factory()->create();
        $closed->forceFill(['finalized_at' => now()])->save();

        $this->actingAs($admin);
        expect(Gate::allows('delete', $open))->toBeTrue()
            ->and(Gate::allows('delete', $closed))->toBeFalse();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('delete', $open))->toBeFalse();
    });
});
