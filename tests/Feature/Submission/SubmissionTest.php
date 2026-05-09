<?php

declare(strict_types=1);

use App\Actions\Assignment\SubmitAssignmentAction;
use App\Enums\Assignment\SubmissionStatus;
use App\Livewire\Assignment\Student\Submission as StudentSubmission;
use App\Models\Assignment;
use App\Models\AssignmentType;
use App\Models\Document;
use App\Models\Internship;
use App\Models\Registration;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('public');

    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);
    Role::create(['name' => 'supervisor', 'guard_name' => 'web']);

    $this->student = User::factory()->create();
    $this->student->assignRole('student');

    $this->internship = Internship::factory()->create();
    $this->type = AssignmentType::factory()->create();

    $this->assignment = Assignment::factory()->published()->create([
        'assignment_type_id' => $this->type->id,
        'internship_id' => $this->internship->id,
        'title' => 'Laporan PKL',
        'description' => 'Buat laporan kegiatan magang.',
    ]);

    $this->registration = Registration::factory()->create([
        'student_id' => $this->student->id,
        'internship_id' => $this->internship->id,
    ]);
    $this->registration->setStatus('active');

    $this->actingAs($this->student);
});

/*
|--------------------------------------------------------------------------
| Rendering
|--------------------------------------------------------------------------
*/

describe('rendering', function () {

    it('renders the submission page with assignments', function () {
        Livewire::test(StudentSubmission::class)
            ->assertSuccessful()
            ->assertSee('Laporan PKL');
    });

    it('shows empty state when no assignments exist', function () {
        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');
        $this->actingAs($otherStudent);

        Livewire::test(StudentSubmission::class)
            ->assertSee('No assignments yet');
    });

    it('shows submission status on each assignment card', function () {
        Livewire::test(StudentSubmission::class)
            ->assertSee('Pending');
    });

});

/*
|--------------------------------------------------------------------------
| Detail View
|--------------------------------------------------------------------------
*/

describe('detail view', function () {

    it('shows assignment detail when clicked', function () {
        Livewire::test(StudentSubmission::class)
            ->call('viewDetail', $this->assignment->id)
            ->assertSet('showDetail', true)
            ->assertSee('Buat laporan kegiatan magang');
    });

    it('shows template download when document is attached', function () {
        $doc = Document::factory()->create(['name' => 'Template Laporan']);
        $this->assignment->update(['document_id' => $doc->id]);

        Livewire::test(StudentSubmission::class)
            ->call('viewDetail', $this->assignment->id)
            ->assertSee('Template Laporan')
            ->assertSee('Download');
    });

    it('goes back to list from detail', function () {
        Livewire::test(StudentSubmission::class)
            ->call('viewDetail', $this->assignment->id)
            ->call('back')
            ->assertSet('showDetail', false)
            ->assertSet('selectedAssignment', null);
    });

});

/*
|--------------------------------------------------------------------------
| Submission
|--------------------------------------------------------------------------
*/

describe('submission', function () {

    it('validates content is required', function () {
        Livewire::test(StudentSubmission::class)
            ->call('viewDetail', $this->assignment->id)
            ->set('content', '')
            ->call('submit', $this->assignment->id)
            ->assertHasErrors(['content' => 'required']);
    });

    it('validates content minimum length', function () {
        Livewire::test(StudentSubmission::class)
            ->call('viewDetail', $this->assignment->id)
            ->set('content', 'Short')
            ->call('submit', $this->assignment->id)
            ->assertHasErrors(['content' => 'min']);
    });

    it('submits an assignment with content only', function () {
        Livewire::test(StudentSubmission::class)
            ->call('viewDetail', $this->assignment->id)
            ->set('content', 'Today I completed the internship report covering all my activities.')
            ->call('submit', $this->assignment->id)
            ->assertHasNoErrors();

        $submission = Submission::where('student_id', $this->student->id)->first();
        expect($submission)->not->toBeNull()
            ->and($submission->content)->toContain('internship report')
            ->and($submission->status->value)->toBe('submitted');
    });

    it('submits with file upload', function () {
        $file = UploadedFile::fake()->create('report.pdf', 500);

        Livewire::test(StudentSubmission::class)
            ->call('viewDetail', $this->assignment->id)
            ->set('content', 'My detailed internship report with attachments.')
            ->set('file', $file)
            ->call('submit', $this->assignment->id)
            ->assertHasNoErrors();

        $submission = Submission::where('student_id', $this->student->id)->first();
        expect($submission)->not->toBeNull();
        expect($submission->getFirstMedia('file'))->not->toBeNull();
    });

    it('shows submitted status after submission', function () {
        Submission::factory()->create([
            'assignment_id' => $this->assignment->id,
            'student_id' => $this->student->id,
            'registration_id' => $this->registration->id,
        ]);

        Livewire::test(StudentSubmission::class)
            ->call('viewDetail', $this->assignment->id)
            ->assertSee('Submitted');
    });

    it('shows verified status when submission is graded', function () {
        Submission::factory()->create([
            'assignment_id' => $this->assignment->id,
            'student_id' => $this->student->id,
            'registration_id' => $this->registration->id,
            'status' => SubmissionStatus::VERIFIED,
            'feedback' => 'Great work!',
        ]);

        Livewire::test(StudentSubmission::class)
            ->call('viewDetail', $this->assignment->id)
            ->assertSee('Verified by mentor')
            ->assertSee('Great work!');
    });

});

/*
|--------------------------------------------------------------------------
| SubmitAssignmentAction (Direct)
|--------------------------------------------------------------------------
*/

describe('SubmitAssignmentAction', function () {

    it('creates submission for valid assignment', function () {
        $submission = app(SubmitAssignmentAction::class)->execute(
            assignment: $this->assignment,
            registrationId: $this->registration->id,
            studentId: $this->student->id,
            content: 'My report content.',
        );

        expect($submission)->toBeInstanceOf(Submission::class)
            ->and($submission->student_id)->toBe($this->student->id)
            ->and($submission->content)->toBe('My report content.')
            ->and($submission->status->value)->toBe('submitted');
    });

    it('throws when assignment is not published', function () {
        $draft = Assignment::factory()->create(['status' => 'draft']);

        expect(fn () => app(SubmitAssignmentAction::class)->execute(
            assignment: $draft,
            registrationId: $this->registration->id,
            studentId: $this->student->id,
            content: 'Content.',
        ))->toThrow(InvalidArgumentException::class, 'unpublished');
    });

    it('throws when assignment is overdue', function () {
        $overdue = Assignment::factory()->published()->create([
            'due_date' => now()->subDay(),
        ]);

        expect(fn () => app(SubmitAssignmentAction::class)->execute(
            assignment: $overdue,
            registrationId: $this->registration->id,
            studentId: $this->student->id,
            content: 'Content.',
        ))->toThrow(InvalidArgumentException::class, 'overdue');
    });

    it('throws on duplicate submission', function () {
        app(SubmitAssignmentAction::class)->execute(
            assignment: $this->assignment,
            registrationId: $this->registration->id,
            studentId: $this->student->id,
            content: 'First submission.',
        );

        expect(fn () => app(SubmitAssignmentAction::class)->execute(
            assignment: $this->assignment,
            registrationId: $this->registration->id,
            studentId: $this->student->id,
            content: 'Duplicate.',
        ))->toThrow(RuntimeException::class, 'already submitted');
    });

    it('creates submission with file via MediaLibrary', function () {
        $file = UploadedFile::fake()->create('report.pdf', 500);

        $submission = app(SubmitAssignmentAction::class)->execute(
            assignment: $this->assignment,
            registrationId: $this->registration->id,
            studentId: $this->student->id,
            content: 'Report with file.',
            file: $file,
        );

        expect($submission->getFirstMedia('file'))->not->toBeNull()
            ->and($submission->getFirstMedia('file')->file_name)->toBe('report.pdf');
    });

});

/*
|--------------------------------------------------------------------------
| Authorization
|--------------------------------------------------------------------------
*/

describe('authorization', function () {

    it('allows student to access', function () {
        Livewire::test(StudentSubmission::class)
            ->assertSuccessful();
    });

});
