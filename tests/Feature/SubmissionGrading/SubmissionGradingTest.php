<?php

declare(strict_types=1);

use App\Actions\Submission\GradeSubmissionAction;
use App\Enums\Assignment\SubmissionStatus;
use App\Livewire\Submission\Grading\SubmissionGrading;
use App\Models\Assignment;
use App\Models\AssignmentType;
use App\Models\Internship;
use App\Models\Registration;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);
    Role::create(['name' => 'supervisor', 'guard_name' => 'web']);

    $this->student = User::factory()->create();
    $this->student->assignRole('student');

    $this->teacher = User::factory()->create();
    $this->teacher->assignRole('teacher');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->supervisor = User::factory()->create();
    $this->supervisor->assignRole('supervisor');

    $this->internship = Internship::factory()->create();
    $this->type = AssignmentType::factory()->create();

    $this->assignment = Assignment::factory()->published()->create([
        'assignment_type_id' => $this->type->id,
        'internship_id' => $this->internship->id,
        'title' => 'Laporan PKL',
    ]);

    $this->registration = Registration::factory()->create([
        'student_id' => $this->student->id,
        'internship_id' => $this->internship->id,
        'teacher_id' => $this->teacher->id,
        'mentor_id' => $this->supervisor->id,
    ]);
    $this->registration->setStatus('active');

    $this->submission = Submission::factory()->create([
        'assignment_id' => $this->assignment->id,
        'registration_id' => $this->registration->id,
        'student_id' => $this->student->id,
        'content' => 'My detailed internship report covering all activities.',
        'status' => SubmissionStatus::SUBMITTED,
        'submitted_at' => now(),
    ]);
});

/*
|--------------------------------------------------------------------------
| Rendering & Authorization
|--------------------------------------------------------------------------
*/

describe('rendering', function () {

    it('allows admin to access', function () {
        $this->actingAs($this->admin);

        Livewire::test(SubmissionGrading::class)
            ->assertSuccessful()
            ->assertSee('Submission Grading');
    });

    it('allows teacher to access', function () {
        $this->actingAs($this->teacher);

        Livewire::test(SubmissionGrading::class)
            ->assertSuccessful();
    });

    it('allows supervisor to access', function () {
        $this->actingAs($this->supervisor);

        Livewire::test(SubmissionGrading::class)
            ->assertSuccessful();
    });

    it('blocks student access', function () {
        $this->actingAs($this->student);

        Livewire::test(SubmissionGrading::class)
            ->assertForbidden();
    });

});

/*
|--------------------------------------------------------------------------
| List View
|--------------------------------------------------------------------------
*/

describe('list view', function () {

    it('shows submissions pending grading', function () {
        $this->actingAs($this->admin);

        Livewire::test(SubmissionGrading::class)
            ->assertSee($this->student->name)
            ->assertSee('Laporan PKL')
            ->assertSee('Submitted');
    });

    it('shows all caught up when no pending submissions', function () {
        $this->actingAs($this->admin);
        $this->submission->update(['status' => SubmissionStatus::VERIFIED]);

        Livewire::test(SubmissionGrading::class)
            ->assertSee('All caught up');
    });

    it('scopes to teacher students only', function () {
        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');

        $otherReg = Registration::factory()->create([
            'student_id' => $otherStudent->id,
            'internship_id' => $this->internship->id,
        ]);
        $otherReg->setStatus('active');

        Submission::factory()->create([
            'assignment_id' => $this->assignment->id,
            'registration_id' => $otherReg->id,
            'student_id' => $otherStudent->id,
            'status' => SubmissionStatus::SUBMITTED,
        ]);

        $this->actingAs($this->teacher);

        Livewire::test(SubmissionGrading::class)
            ->assertSee($this->student->name)
            ->assertDontSee($otherStudent->name);
    });

    it('scopes to supervisor students only', function () {
        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');

        $otherReg = Registration::factory()->create([
            'student_id' => $otherStudent->id,
            'internship_id' => $this->internship->id,
        ]);
        $otherReg->setStatus('active');

        Submission::factory()->create([
            'assignment_id' => $this->assignment->id,
            'registration_id' => $otherReg->id,
            'student_id' => $otherStudent->id,
            'status' => SubmissionStatus::SUBMITTED,
        ]);

        $this->actingAs($this->supervisor);

        Livewire::test(SubmissionGrading::class)
            ->assertSee($this->student->name)
            ->assertDontSee($otherStudent->name);
    });

    it('shows submissions in revision_required status', function () {
        $this->submission->update(['status' => SubmissionStatus::REVISION_REQUIRED]);
        $this->actingAs($this->admin);

        Livewire::test(SubmissionGrading::class)
            ->assertSee($this->student->name)
            ->assertSee('Revision Required');
    });

});

/*
|--------------------------------------------------------------------------
| Detail View
|--------------------------------------------------------------------------
*/

describe('detail view', function () {

    beforeEach(function () {
        $this->actingAs($this->admin);
    });

    it('shows submission detail when clicked', function () {
        Livewire::test(SubmissionGrading::class)
            ->call('viewSubmission', $this->submission->id)
            ->assertSet('selectedSubmissionId', $this->submission->id)
            ->assertSee($this->student->name)
            ->assertSee('Laporan PKL')
            ->assertSee('My detailed internship report');
    });

    it('goes back to list from detail', function () {
        Livewire::test(SubmissionGrading::class)
            ->call('viewSubmission', $this->submission->id)
            ->call('back')
            ->assertSet('selectedSubmission', null)
            ->assertSet('selectedSubmissionId', null);
    });

});

/*
|--------------------------------------------------------------------------
| Grading
|--------------------------------------------------------------------------
*/

describe('grading', function () {

    beforeEach(function () {
        $this->actingAs($this->admin);
        Notification::fake();
    });

    it('validates score is required', function () {
        Livewire::test(SubmissionGrading::class)
            ->call('viewSubmission', $this->submission->id)
            ->set('score', null)
            ->call('grade')
            ->assertHasErrors(['score' => 'required']);
    });

    it('validates score between 0 and 100', function () {
        Livewire::test(SubmissionGrading::class)
            ->call('viewSubmission', $this->submission->id)
            ->set('score', 150)
            ->call('grade')
            ->assertHasErrors(['score' => 'max']);
    });

    it('validates feedback is required', function () {
        Livewire::test(SubmissionGrading::class)
            ->call('viewSubmission', $this->submission->id)
            ->set('feedback', '')
            ->call('grade')
            ->assertHasErrors(['feedback' => 'required']);
    });

    it('validates feedback minimum length', function () {
        Livewire::test(SubmissionGrading::class)
            ->call('viewSubmission', $this->submission->id)
            ->set('feedback', 'Short')
            ->call('grade')
            ->assertHasErrors(['feedback' => 'min']);
    });

    it('grades submission as verified', function () {
        app(GradeSubmissionAction::class)->execute(
            submission: $this->submission,
            grader: $this->admin,
            score: 88,
            status: 'verified',
            feedback: 'Great work on the report! Very detailed.',
        );

        $this->assertDatabaseHas('submissions', [
            'id' => $this->submission->id,
            'status' => 'verified',
            'score' => 88,
        ]);
    });

    it('grades submission via livewire component', function () {
        Livewire::test(SubmissionGrading::class)
            ->call('viewSubmission', $this->submission->id)
            ->set('score', 88)
            ->set('feedback', 'Great work on the report! Very detailed.')
            ->set('gradeStatus', 'verified')
            ->call('grade')
            ->assertHasNoErrors()
            ->assertSet('selectedSubmission', null);
    });

    it('resets form after grading', function () {
        Livewire::test(SubmissionGrading::class)
            ->call('viewSubmission', $this->submission->id)
            ->set('score', 90)
            ->set('feedback', 'Excellent submission!')
            ->call('grade');

        Livewire::test(SubmissionGrading::class)
            ->assertSet('selectedSubmission', null);
    });

});

/*
|--------------------------------------------------------------------------
| GradeSubmissionAction (Direct)
|--------------------------------------------------------------------------
*/

describe('GradeSubmissionAction', function () {

    it('grades submission successfully', function () {
        $submission = app(GradeSubmissionAction::class)->execute(
            submission: $this->submission,
            grader: $this->admin,
            score: 90,
            status: SubmissionStatus::VERIFIED,
            feedback: 'Outstanding work!',
        );

        expect($submission)->toBeInstanceOf(Submission::class)
            ->and($submission->score)->toBe(90.0)
            ->and($submission->status->value)->toBe('verified')
            ->and($submission->graded_by)->toBe($this->admin->id)
            ->and($submission->graded_at)->not->toBeNull();
    });

    it('throws when score is below 0', function () {
        expect(fn () => app(GradeSubmissionAction::class)->execute(
            submission: $this->submission,
            grader: $this->admin,
            score: -1,
            status: SubmissionStatus::VERIFIED,
        ))->toThrow(InvalidArgumentException::class, 'between 0 and 100');
    });

    it('throws when score is above 100', function () {
        expect(fn () => app(GradeSubmissionAction::class)->execute(
            submission: $this->submission,
            grader: $this->admin,
            score: 101,
            status: SubmissionStatus::VERIFIED,
        ))->toThrow(InvalidArgumentException::class, 'between 0 and 100');
    });

    it('throws when status is invalid', function () {
        expect(fn () => app(GradeSubmissionAction::class)->execute(
            submission: $this->submission,
            grader: $this->admin,
            score: 80,
            status: SubmissionStatus::DRAFT,
        ))->toThrow(InvalidArgumentException::class, 'Invalid grading status');
    });

    it('throws when grader is not authorized', function () {
        expect(fn () => app(GradeSubmissionAction::class)->execute(
            submission: $this->submission,
            grader: $this->student,
            score: 80,
            status: SubmissionStatus::VERIFIED,
        ))->toThrow(InvalidArgumentException::class, 'Not authorized');
    });

    it('allows supervisor to grade', function () {
        $submission = app(GradeSubmissionAction::class)->execute(
            submission: $this->submission,
            grader: $this->supervisor,
            score: 75,
            status: SubmissionStatus::VERIFIED,
            feedback: 'Good progress!',
        );

        expect($submission->score)->toBe(75.0);
    });

    it('allows teacher to grade', function () {
        $submission = app(GradeSubmissionAction::class)->execute(
            submission: $this->submission,
            grader: $this->teacher,
            score: 85,
            status: SubmissionStatus::REVISION_REQUIRED,
            feedback: 'Needs more detail.',
        );

        expect($submission->status->value)->toBe('revision_required');
    });

});

/*
|--------------------------------------------------------------------------
| Authorization
|--------------------------------------------------------------------------
*/

describe('authorization', function () {

    it('allows admin to view any submission', function () {
        $this->actingAs($this->admin);

        Livewire::test(SubmissionGrading::class)
            ->call('viewSubmission', $this->submission->id)
            ->assertSuccessful();
    });

    it('allows teacher to view own students submission', function () {
        $this->actingAs($this->teacher);

        Livewire::test(SubmissionGrading::class)
            ->call('viewSubmission', $this->submission->id)
            ->assertSuccessful();
    });

    it('allows supervisor to view own students submission', function () {
        $this->actingAs($this->supervisor);

        Livewire::test(SubmissionGrading::class)
            ->call('viewSubmission', $this->submission->id)
            ->assertSuccessful();
    });

});
