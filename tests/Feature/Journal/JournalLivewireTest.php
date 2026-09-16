<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journal\Domain\Attendance\Livewire\StudentClockIn;
use App\Modules\Journal\Domain\Attendance\Models\Attendance;
use App\Modules\Journal\Domain\Logbook\Livewire\LogbookEntry;
use App\Modules\Journal\Domain\Logbook\Models\Logbook;
use App\Modules\Journal\Domain\MonitoringVisit\Enums\VisitMethod;
use App\Modules\Journal\Domain\MonitoringVisit\Livewire\VisitManager;
use App\Modules\Journal\Domain\MonitoringVisit\Models\MonitoringVisit;
use App\Modules\Journal\Domain\SupervisionLog\Livewire\StudentLogManager;
use App\Modules\Journal\Domain\SupervisionLog\Livewire\SupervisorReviewManager;
use App\Modules\Journal\Domain\SupervisionLog\Models\SupervisionLog;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->student = User::factory()->create();
    $this->student->assignRole('student');
    $this->registration = Registration::factory()->active()->create([
        'student_id' => $this->student->id,
        'start_date' => now()->subWeek(),
        'end_date' => now()->addMonth(),
    ]);
    $this->teacher = User::factory()->create();
    $this->teacher->assignRole('teacher');
    $this->supervisor = User::factory()->create();
    $this->supervisor->assignRole('supervisor');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('1KSWL-UC-DAILY-003 / 1KSWL-FR-DAILY-014: student clock-in Livewire action persists attendance', function (): void {
    $this->actingAs($this->student);

    Livewire::test(StudentClockIn::class)->call('clockIn');

    expect(Attendance::where('user_id', $this->student->id)->whereDate('date', today())->exists())->toBeTrue();
});

test('1KSWL-FR-DAILY-008 / 1KSWL-FR-DAILY-016: student clock-out Livewire action closes attendance', function (): void {
    $this->actingAs($this->student);
    Attendance::factory()->create([
        'user_id' => $this->student->id,
        'registration_id' => $this->registration->id,
        'date' => today(),
        'clock_out' => null,
    ]);

    Livewire::test(StudentClockIn::class)->call('clockOut');

    expect(Attendance::where('user_id', $this->student->id)->whereDate('date', today())->first()->clock_out)->not->toBeNull();
});

test('1KSWL-UC-DAILY-001 / 1KSWL-FR-DAILY-015: logbook Livewire validates and submits the student form', function (): void {
    $this->actingAs($this->student);

    Livewire::test(LogbookEntry::class)
        ->set('content', 'Recorded the completed quality-control task.')
        ->set('learning_outcomes', 'Learned the inspection checklist.')
        ->call('save');

    expect(Logbook::where('user_id', $this->student->id)->whereDate('date', today())->value('status')->value)
        ->toBe('submitted');
});

test('1KSWL-FR-DAILY-014 / 1KSWL-FR-DAILY-016: logbook Livewire renders only the signed-in student records', function (): void {
    $other = User::factory()->create();
    Logbook::factory()->create(['user_id' => $this->student->id]);
    Logbook::factory()->create(['user_id' => $other->id]);
    $this->actingAs($this->student);

    $component = Livewire::test(LogbookEntry::class);

    expect($component->viewData('journals')->pluck('user_id')->unique()->all())->toBe([$this->student->id]);
});

test('2EHSE-UC-SUPV-001 / 2EHSE-NFR-SUPV-001: visit manager saves the full visit form', function (): void {
    $this->actingAs($this->teacher);
    InternshipGroupMember::factory()->create([
        'registration_id' => $this->registration->id,
        'user_id' => $this->teacher->id,
    ]);

    Livewire::test(VisitManager::class)
        ->set('registrationId', $this->registration->id)
        ->set('visitDate', today()->toDateString())
        ->set('method', VisitMethod::VIRTUAL_MEETING->value)
        ->set('location', 'Video call')
        ->set('durationMinutes', 45)
        ->set('notes', 'Discussed progress')
        ->call('save');

    expect(MonitoringVisit::where('registration_id', $this->registration->id)->value('method')->value)
        ->toBe(VisitMethod::VIRTUAL_MEETING->value);
});

test('2EHSE-FR-SUPV-011 / 2EHSE-NFR-SUPV-004: student supervision Livewire saves a log for the active registration', function (): void {
    $this->actingAs($this->student);
    Livewire::test(StudentLogManager::class)
        ->set('supervisorId', $this->supervisor->id)
        ->set('date', today()->toDateString())
        ->set('topic', 'Workplace mentoring')
        ->set('notes', 'Discussed the next task.')
        ->call('save');

    expect(SupervisionLog::where('registration_id', $this->registration->id)->value('supervisor_id'))
        ->toBe($this->supervisor->id);
});

test('2EHSE-UC-SUPV-002 / 2EHSE-FR-SUPV-004: supervisor review Livewire persists required feedback', function (): void {
    $log = SupervisionLog::factory()->create([
        'registration_id' => $this->registration->id,
        'supervisor_id' => $this->supervisor->id,
        'status' => 'submitted',
    ]);
    $this->actingAs($this->supervisor);

    Livewire::test(SupervisorReviewManager::class)
        ->set('reviewTarget', $log->id)
        ->set('feedback', 'Please include the safety evidence next time.')
        ->call('confirmReview');

    expect($log->fresh()->status->value)->toBe('reviewed')
        ->and($log->fresh()->supervisor_feedback)->toBe('Please include the safety evidence next time.');
});
