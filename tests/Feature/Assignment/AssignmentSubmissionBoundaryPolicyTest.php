<?php

declare(strict_types=1);

use App\Modules\Assignment\Domain\Submission\Models\Submission;
use App\Modules\Assignment\Models\Assignment;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(LazilyRefreshDatabase::class);

test('T657Z-FR-ASG-011: supervisor and student cannot author assignments', function (): void {
    $assignment = Assignment::factory()->create();

    foreach (['supervisor', 'student'] as $role) {
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        expect(Gate::allows('create', Assignment::class))->toBeFalse()
            ->and(Gate::allows('update', $assignment))->toBeFalse()
            ->and(Gate::allows('publish', $assignment))->toBeFalse();
    }
});

test('T657B-FR-SUBM-012: only the submission owner can update submitted work', function (): void {
    $owner = User::factory()->create();
    $owner->assignRole('student');
    $outsider = User::factory()->create();
    $outsider->assignRole('student');
    $submission = Submission::factory()->create([
        'student_id' => $owner->id,
        'status' => 'submitted',
    ]);

    $this->actingAs($owner);
    expect(Gate::allows('update', $submission))->toBeTrue();

    $this->actingAs($outsider);
    expect(Gate::allows('update', $submission))->toBeFalse();
});

test('T657C-FR-GRADE-011: students cannot verify submissions', function (): void {
    $student = User::factory()->create();
    $student->assignRole('student');
    $submission = Submission::factory()->create(['student_id' => $student->id]);

    $this->actingAs($student);

    expect(Gate::allows('verify', $submission))->toBeFalse();
});

test('T657C-FR-GRADE-013: administrators can verify and delete submissions', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $submission = Submission::factory()->create();

    $this->actingAs($admin);

    expect(Gate::allows('verify', $submission))->toBeTrue()
        ->and(Gate::allows('delete', $submission))->toBeTrue();
});
