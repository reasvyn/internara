<?php

declare(strict_types=1);

namespace Tests\Feature\Assignment;

use App\Modules\Assignment\Domain\Submission\Actions\SubmitAssignmentAction;
use App\Modules\Assignment\Domain\Submission\Livewire\SubmissionGrading;
use App\Modules\Assignment\Domain\Submission\Livewire\SubmitAssignment;
use App\Modules\Assignment\Domain\Submission\Models\Submission;
use App\Modules\Assignment\Models\Assignment;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('T657B / T657Z: Submission Livewire UI Components', function (): void {
    test('T657Z-FR-SUB-001: SubmissionGrading renders for teacher and handles submission view and back transitions', function (): void {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);

        $student = User::factory()->create();
        $student->assignRole('student');

        $internship = Internship::factory()->create();
        $assignment = Assignment::factory()->create(['internship_id' => $internship->id]);

        $registration = Registration::factory()->create([
            'student_id' => $student->id,
            'internship_id' => $internship->id,
            'status' => 'active',
        ]);

        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'registration_id' => $registration->id,
            'student_id' => $student->id,
            'content' => 'Comprehensive vocational task report submission',
        ]);

        $test = Livewire::test(SubmissionGrading::class);
        $test->assertOk();

        $test->call('viewSubmission', (string) $submission->id);
        expect($test->get('selectedSubmissionId'))->toBe((string) $submission->id)
            ->and($test->get('selectedSubmission'))->not->toBeNull();

        $test->call('back');
        expect($test->get('selectedSubmissionId'))->toBeNull()
            ->and($test->get('selectedSubmission'))->toBeNull();
    });

    test('T657B-FR-SUB-002: SubmitAssignment manages viewDetail toggle and back transitions', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);

        $assignment = Assignment::factory()->create();

        $test = Livewire::test(SubmitAssignment::class);
        $test->assertOk()
            ->assertSet('showDetail', false);

        $test->call('viewDetail', $assignment);
        expect($test->get('showDetail'))->toBeTrue()
            ->and($test->get('assignmentId'))->toBe((string) $assignment->id);

        $test->call('back');
        expect($test->get('showDetail'))->toBeFalse()
            ->and($test->get('selectedAssignment'))->toBeNull();
    });

    test('T657B-FR-SUB-003: SubmitAssignment validates required content length on submission', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);

        $assignment = Assignment::factory()->create();

        Livewire::test(SubmitAssignment::class)
            ->set('assignmentId', (string) $assignment->id)
            ->set('content', 'Too short')
            ->call('submit', app(SubmitAssignmentAction::class))
            ->assertHasErrors(['content' => 'min']);
    });

    test('T657B-FR-SUB-004: SubmitAssignment persists uploaded file attachment into submission media collection', function (): void {
        Storage::fake('public');
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);

        $internship = Internship::factory()->create();
        $assignment = Assignment::factory()->published()->create(['internship_id' => $internship->id]);

        Registration::factory()->create([
            'student_id' => $student->id,
            'internship_id' => $internship->id,
            'status' => 'active',
        ]);

        $file = UploadedFile::fake()->create('report.pdf', 500, 'application/pdf');

        Livewire::test(SubmitAssignment::class)
            ->set('assignmentId', (string) $assignment->id)
            ->set('content', 'This is a sufficiently long report submission content with file.')
            ->set('file', $file)
            ->call('submit');

        $submission = Submission::where('student_id', $student->id)->where('assignment_id', $assignment->id)->first();
        expect($submission)->not->toBeNull()
            ->and($submission->getFirstMedia('file'))->not->toBeNull();
    });
});
