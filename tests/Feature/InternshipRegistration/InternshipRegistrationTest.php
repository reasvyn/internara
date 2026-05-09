<?php

declare(strict_types=1);

use App\Actions\Internship\DirectPlacementAction;
use App\Actions\Internship\RegisterInternshipAction;
use App\Actions\Internship\VerifyAccountAction;
use App\Livewire\Internship\AccountApplicationForm;
use App\Livewire\Internship\ApplicationReview;
use App\Livewire\Internship\RegistrationWizard;
use App\Models\AccountApplication;
use App\Models\Internship;
use App\Models\Mentee;
use App\Models\Mentor;
use App\Models\Placement;
use App\Models\Registration;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    foreach (['super_admin', 'admin', 'teacher', 'student', 'supervisor'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    $this->admin = User::factory()->create()->assignRole('admin');
    $this->student = User::factory()->create()->assignRole('student');

    $this->internship = Internship::factory()->create([
        'status' => 'published',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonths(5),
    ]);

    $this->placement = Placement::factory()->create([
        'internship_id' => $this->internship->id,
        'quota' => 10,
        'filled_quota' => 0,
    ]);
});

// ─── Account Application (Public) ───────────────────────────────────

it('guest can see application form', function () {
    $this->get(route('apply'))
        ->assertSuccessful()
        ->assertSee('Account & Internship Application');
});

it('guest can submit an application', function () {
    Livewire::test(AccountApplicationForm::class)
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->set('internship_id', $this->internship->id)
        ->set('placement_id', $this->placement->id)
        ->set('academic_year', '2025/2026')
        ->call('submit')
        ->assertHasNoErrors();

    expect(AccountApplication::where('email', 'john@example.com')->exists())->toBeTrue();
});

it('validates required fields on application', function () {
    Livewire::test(AccountApplicationForm::class)
        ->call('submit')
        ->assertHasErrors(['name', 'email', 'internship_id', 'academic_year']);
});

it('rejects duplicate email on application', function () {
    AccountApplication::create([
        'name' => 'Existing',
        'email' => 'dup@example.com',
        'internship_id' => $this->internship->id,
        'academic_year' => '2025/2026',
    ]);

    Livewire::test(AccountApplicationForm::class)
        ->set('name', 'Duplicate')
        ->set('email', 'dup@example.com')
        ->set('internship_id', $this->internship->id)
        ->set('placement_id', $this->placement->id)
        ->set('academic_year', '2025/2026')
        ->call('submit')
        ->assertHasErrors(['email']);
});

// ─── Admin Application Review ───────────────────────────────────────

it('admin can view pending applications', function () {
    AccountApplication::create([
        'name' => 'Pending',
        'email' => 'pending@example.com',
        'internship_id' => $this->internship->id,
        'academic_year' => '2025/2026',
    ]);

    Livewire::actingAs($this->admin)
        ->test(ApplicationReview::class)
        ->assertSee('Pending');
});

it('admin can approve an application', function () {
    $app = AccountApplication::create([
        'name' => 'New Student',
        'email' => 'new@example.com',
        'internship_id' => $this->internship->id,
        'placement_id' => $this->placement->id,
        'academic_year' => '2025/2026',
    ]);

    $action = app(VerifyAccountAction::class);
    $registration = $action->approve($app->id, $this->admin);

    $app->refresh();

    expect($app->status)->toBe('approved');
    expect($app->processed_by)->toBe($this->admin->id);
    expect($registration)->toBeInstanceOf(Registration::class);
    expect($registration->mentee->user->email)->toBe('new@example.com');
    expect($registration->status)->toBe('active');
});

it('admin can reject an application', function () {
    $app = AccountApplication::create([
        'name' => 'Reject Me',
        'email' => 'reject@example.com',
        'internship_id' => $this->internship->id,
        'academic_year' => '2025/2026',
    ]);

    $action = app(VerifyAccountAction::class);
    $action->reject($app->id, $this->admin, 'Incomplete documentation');

    $app->refresh();

    expect($app->status)->toBe('rejected');
    expect($app->rejection_reason)->toBe('Incomplete documentation');
});

it('cannot approve already processed application', function () {
    $app = AccountApplication::create([
        'name' => 'Processed',
        'email' => 'processed@example.com',
        'internship_id' => $this->internship->id,
        'placement_id' => $this->placement->id,
        'academic_year' => '2025/2026',
        'status' => 'approved',
    ]);

    $action = app(VerifyAccountAction::class);

    expect(fn () => $action->approve($app->id, $this->admin))
        ->toThrow(RuntimeException::class, 'not in pending status');
});

// ─── Self-Service Registration (existing student) ───────────────────

it('student can self-register', function () {
    $action = app(RegisterInternshipAction::class);
    $registration = $action->execute($this->student, [
        'internship_id' => $this->internship->id,
        'placement_id' => $this->placement->id,
        'academic_year' => '2025/2026',
    ]);

    expect($registration->mentee->user_id)->toBe($this->student->id);
    expect($registration->hasStatus('pending'))->toBeTrue();
});

it('self-registration fails if student already has active or pending registration', function () {
    $mentee = Mentee::create(['user_id' => $this->student->id]);
    $existing = Registration::create([
        'mentee_id' => $mentee->id,
        'internship_id' => $this->internship->id,
    ]);
    $existing->setStatus('active', 'test');

    $action = app(RegisterInternshipAction::class);

    expect(fn () => $action->execute($this->student, [
        'internship_id' => $this->internship->id,
        'placement_id' => $this->placement->id,
        'academic_year' => '2025/2026',
    ]))->toThrow(RuntimeException::class, 'already has an active or pending');
});

it('self-registration checks registration period', function () {
    $closedInternship = Internship::factory()->create(['status' => 'completed']);

    Livewire::actingAs($this->student)
        ->test(RegistrationWizard::class)
        ->set('data.internship_id', $closedInternship->id)
        ->set('data.academic_year', '2025/2026')
        ->call('submit')
        ->assertHasErrors(['data.internship_id']);
});

// ─── Admin Direct Placement ─────────────────────────────────────────

it('admin can direct-place a student', function () {
    $mentor = Mentor::factory()->create(['type' => 'school_teacher']);

    $action = app(DirectPlacementAction::class);
    $registration = $action->execute($this->student, [
        'placement_id' => $this->placement->id,
        'academic_year' => '2025/2026',
        'mentor_ids' => [$mentor->id],
    ]);

    expect($registration->hasStatus('active'))->toBeTrue();
    expect($registration->mentors()->count())->toBe(1);
    expect($registration->mentors()->first()->id)->toBe($mentor->id);
});

it('direct placement fails if placement is full', function () {
    $fullPlacement = Placement::factory()->create([
        'internship_id' => $this->internship->id,
        'quota' => 1,
        'filled_quota' => 1,
    ]);

    $action = app(DirectPlacementAction::class);

    try {
        $action->execute($this->student, [
            'placement_id' => $fullPlacement->id,
            'academic_year' => '2025/2026',
        ]);
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(422);

        return;
    }

    $this->fail('Expected HttpException was not thrown.');
});

// ─── Mentee State Business Rules ───────────────────────────────────

it('mentee state reflects active registration', function () {
    $mentee = Mentee::create(['user_id' => $this->student->id]);
    $registration = Registration::create([
        'mentee_id' => $mentee->id,
        'internship_id' => $this->internship->id,
        'start_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
    ]);
    $registration->setStatus('active', 'test');

    $state = $mentee->asMenteeState();
    expect($state->hasActiveRegistration())->toBeTrue();
    expect($state->isWithinInternshipPeriod())->toBeTrue();
    expect($state->canClockIn())->toBeTrue();
    expect($state->canSubmitLogbook())->toBeTrue();
});

// ─── Mentor Role Business Rules ─────────────────────────────────────

it('mentor role distinguishes teacher from supervisor', function () {
    $teacher = Mentor::factory()->create(['type' => 'school_teacher']);
    $supervisor = Mentor::factory()->create(['type' => 'industry_supervisor']);

    expect($teacher->asMentorRole()->isSchoolTeacher())->toBeTrue();
    expect($teacher->asMentorRole()->canVerifyAttendance())->toBeTrue();
    expect($teacher->asMentorRole()->canGradeSubmission())->toBeTrue();

    expect($supervisor->asMentorRole()->isIndustrySupervisor())->toBeTrue();
    expect($supervisor->asMentorRole()->canVerifyAttendance())->toBeFalse();
    expect($supervisor->asMentorRole()->canGradeSubmission())->toBeFalse();

    // Both can create supervision logs
    expect($teacher->asMentorRole()->canCreateSupervisionLog())->toBeTrue();
    expect($supervisor->asMentorRole()->canCreateSupervisionLog())->toBeTrue();
});
