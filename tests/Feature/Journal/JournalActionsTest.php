<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journal\Domain\AbsenceRequest\Actions\ProcessAbsenceAction;
use App\Modules\Journal\Domain\AbsenceRequest\Actions\SubmitAbsenceAction;
use App\Modules\Journal\Domain\AbsenceRequest\Data\ProcessAbsenceData;
use App\Modules\Journal\Domain\AbsenceRequest\Data\SubmitAbsenceData;
use App\Modules\Journal\Domain\AbsenceRequest\Enums\AbsenceReasonType;
use App\Modules\Journal\Domain\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Modules\Journal\Domain\Attendance\Actions\ClockInAction;
use App\Modules\Journal\Domain\Attendance\Actions\ClockOutAction;
use App\Modules\Journal\Domain\Attendance\Data\ClockInData;
use App\Modules\Journal\Domain\Attendance\Data\ClockOutData;
use App\Modules\Journal\Domain\Attendance\Models\Attendance;
use App\Modules\Journal\Domain\Logbook\Actions\CreateLogbookAction;
use App\Modules\Journal\Domain\Logbook\Actions\SubmitLogbookAction;
use App\Modules\Journal\Domain\Logbook\Actions\UpdateLogbookAction;
use App\Modules\Journal\Domain\Logbook\Enums\LogbookStatus;
use App\Modules\Journal\Domain\Logbook\Models\Logbook;
use App\Modules\Journal\Domain\MonitoringVisit\Actions\CreateVisitAction;
use App\Modules\Journal\Domain\MonitoringVisit\Actions\VerifyVisitAction;
use App\Modules\Journal\Domain\MonitoringVisit\Data\CreateVisitData;
use App\Modules\Journal\Domain\MonitoringVisit\Models\MonitoringVisit;
use App\Modules\Journal\Domain\SupervisionLog\Actions\CreateLogAction;
use App\Modules\Journal\Domain\SupervisionLog\Actions\CreateSupervisionLogAction;
use App\Modules\Journal\Domain\SupervisionLog\Actions\DeleteLogAction;
use App\Modules\Journal\Domain\SupervisionLog\Actions\ReviewLogAction;
use App\Modules\Journal\Domain\SupervisionLog\Actions\VerifySupervisionLogAction;
use App\Modules\Journal\Domain\SupervisionLog\Data\CreateLogData;
use App\Modules\Journal\Domain\SupervisionLog\Data\CreateSupervisionLogData;
use App\Modules\Journal\Domain\SupervisionLog\Data\ReviewLogData;
use App\Modules\Journal\Domain\SupervisionLog\Enums\SupervisionLogStatus;
use App\Modules\Journal\Domain\SupervisionLog\Models\SupervisionLog;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->student = User::factory()->create();
    $this->student->assignRole('student');
    $this->registration = Registration::factory()->active()->create([
        'student_id' => $this->student->id,
        'start_date' => now()->subWeek(),
        'end_date' => now()->addMonth(),
    ]);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->teacher = User::factory()->create();
    $this->teacher->assignRole('teacher');
    $this->supervisor = User::factory()->create();
    $this->supervisor->assignRole('supervisor');
});

describe('Journal command actions', function (): void {
    test('1KSWL-UC-DAILY-001 / 1KSWL-FR-DAILY-001 / 1KSWL-NFR-DAILY-002: submits one current-day logbook', function (): void {
        $this->actingAs($this->student);

        $entry = app(SubmitLogbookAction::class)->execute($this->student, [
            'content' => 'Completed the wiring inspection and recorded the findings.',
            'learning_outcomes' => 'Improved fault isolation.',
        ]);

        expect($entry->status)->toBe(LogbookStatus::SUBMITTED)
            ->and(Logbook::where('user_id', $this->student->id)->whereDate('date', today())->count())->toBe(1);
    });

    test('1KSWL-FR-DAILY-004: accepts a validated photo through the photos collection', function (): void {
        Storage::fake('public');
        $this->actingAs($this->student);
        $photo = UploadedFile::fake()->image('inspection.jpg');

        $entry = app(SubmitLogbookAction::class)->execute($this->student, [
            'content' => 'Attached the inspection photo for today.',
            'photos' => [$photo],
        ]);

        expect($entry->getMedia('photos'))->toHaveCount(1);
    });

    test('1KSWL-FR-DAILY-005 / 1KSWL-FR-DAILY-015: creates a draft only for an active registration', function (): void {
        $this->actingAs($this->student);
        $entry = app(CreateLogbookAction::class)->execute($this->student->id, [
            'date' => today()->toDateString(),
            'content' => 'A draft created through the command boundary.',
        ]);

        expect($entry->status)->toBe(LogbookStatus::DRAFT)
            ->and($entry->registration_id)->toBe($this->registration->id);
    });

    test('1KSWL-FR-DAILY-018: rejects a second submitted logbook for the same day', function (): void {
        $this->actingAs($this->student);
        app(SubmitLogbookAction::class)->execute($this->student, ['content' => 'The first submitted entry for today.']);

        expect(fn () => app(SubmitLogbookAction::class)->execute($this->student, ['content' => 'A duplicate submission.']))
            ->toThrow(RejectedException::class);
    });

    test('1KSWL-FR-DAILY-006 / 1KSWL-FR-DAILY-017: records verifier data when a logbook is updated as verified', function (): void {
        $this->actingAs($this->admin);
        $entry = Logbook::factory()->create([
            'user_id' => $this->student->id,
            'registration_id' => $this->registration->id,
            'status' => LogbookStatus::SUBMITTED,
        ]);

        $updated = app(UpdateLogbookAction::class)->execute($entry, ['is_verified' => true]);

        expect($updated->fresh()->is_verified)->toBeTrue()
            ->and($updated->fresh()->verified_by)->toBe($this->admin->id)
            ->and($updated->fresh()->verified_at)->not->toBeNull();
    });

    test('1KSWL-FR-DAILY-002 / 1KSWL-FR-DAILY-010: stores typed logbook and attendance model contracts', function (): void {
        $logbook = Logbook::factory()->create(['registration_id' => $this->registration->id, 'user_id' => $this->student->id]);
        $attendance = Attendance::factory()->create(['registration_id' => $this->registration->id, 'user_id' => $this->student->id, 'date' => today()->subDay()]);

        expect($logbook->date)->toBeInstanceOf(Carbon::class)
            ->and($logbook->status)->toBeInstanceOf(LogbookStatus::class)
            ->and($logbook->registration->is($this->registration))->toBeTrue()
            ->and($attendance->registration->is($this->registration))->toBeTrue();
    });

    test('1KSWL-UC-DAILY-003 / 1KSWL-FR-DAILY-007 / 1KSWL-NFR-DAILY-003: clocks in once with server and request metadata', function (): void {
        Event::fake();
        $this->actingAs($this->student);

        $attendance = app(ClockInAction::class)->execute(new ClockInData(
            userId: $this->student->id,
            data: ['latitude' => -6.2, 'longitude' => 106.8],
            requestIp: '192.0.2.10',
        ));

        expect($attendance->status->value)->toBe('present')
            ->and($attendance->clock_in)->not->toBeNull()
            ->and($attendance->clock_in_ip)->toBe('192.0.2.10')
            ->and($attendance->clock_in_latitude)->toBe(-6.2)
            ->and(Attendance::where('user_id', $this->student->id)->whereDate('date', today())->count())->toBe(1);
    });

    test('1KSWL-FR-DAILY-007 / 1KSWL-FR-DAILY-010: refuses a duplicate clock-in for one day', function (): void {
        $this->actingAs($this->student);
        app(ClockInAction::class)->execute(new ClockInData($this->student->id));

        expect(fn () => app(ClockInAction::class)->execute(new ClockInData($this->student->id)))
            ->toThrow(RejectedException::class);
    });

    test('1KSWL-UC-DAILY-003 / 1KSWL-FR-DAILY-008: clocks out an open record and rejects a second close', function (): void {
        $this->actingAs($this->student);
        $attendance = app(ClockInAction::class)->execute(new ClockInData($this->student->id));
        $closed = app(ClockOutAction::class)->execute(new ClockOutData($this->student->id, [], '192.0.2.11'));

        expect($closed->fresh()->clock_out)->not->toBeNull()
            ->and($closed->fresh()->clock_out_ip)->toBe('192.0.2.11')
            ->and(fn () => app(ClockOutAction::class)->execute(new ClockOutData($this->student->id, [])))
            ->toThrow(RejectedException::class);
    });

    test('1KSWL-FR-DAILY-008: refuses clock-out before clock-in', function (): void {
        expect(fn () => app(ClockOutAction::class)->execute(new ClockOutData($this->student->id, [])))
            ->toThrow(RejectedException::class);
    });

    test('1KSWL-FR-DAILY-012 / 1KSWL-UC-DAILY-004: files a pending absence as an attendance record', function (): void {
        $attendance = app(SubmitAbsenceAction::class)->execute(new SubmitAbsenceData(
            userId: $this->student->id,
            registrationId: $this->registration->id,
            data: [
                'start_date' => today()->subDay()->toDateString(),
                'reason_type' => AbsenceReasonType::SICK->value,
                'reason_description' => 'Clinic appointment',
                'attachment_path' => 'absence/clinic-note.pdf',
            ],
        ));

        expect($attendance->status->value)->toBe('absent')
            ->and($attendance->absence_status)->toBe(AbsenceRequestStatus::PENDING->value)
            ->and($attendance->absence_attachment)->toBe('absence/clinic-note.pdf');
    });

    test('1KSWL-FR-DAILY-013 / 1KSWL-UC-DAILY-005 / 1KSWL-NFR-DAILY-004: processes an absence once with actor and notes', function (): void {
        $absence = app(SubmitAbsenceAction::class)->execute(new SubmitAbsenceData(
            $this->student->id,
            $this->registration->id,
            ['reason_type' => AbsenceReasonType::PERMISSION->value, 'reason_description' => 'Family matter'],
        ));

        $processed = app(ProcessAbsenceAction::class)->execute(new ProcessAbsenceData(
            absenceId: $absence->id,
            processorId: $this->admin->id,
            status: AbsenceRequestStatus::APPROVED,
            notes: 'Approved by administration.',
        ));

        expect($processed->fresh()->absence_status)->toBe(AbsenceRequestStatus::APPROVED->value)
            ->and($processed->fresh()->absence_processed_by)->toBe($this->admin->id)
            ->and($processed->fresh()->absence_admin_notes)->toBe('Approved by administration.')
            ->and(fn () => app(ProcessAbsenceAction::class)->execute(new ProcessAbsenceData(
                $absence->id, $this->admin->id, AbsenceRequestStatus::REJECTED,
            )))->toThrow(RejectedException::class);
    });

    test('2EHSE-UC-SUPV-001 / 2EHSE-FR-SUPV-008 / 2EHSE-FR-SUPV-016: creates a monitoring visit with its account', function (): void {
        $visit = app(CreateVisitAction::class)->execute(new CreateVisitData(
            teacherId: $this->teacher->id,
            registrationId: $this->registration->id,
            data: [
                'visit_date' => today()->toDateString(),
                'method' => 'site_visit',
                'location' => 'Workshop line 2',
                'duration_minutes' => 90,
                'notes' => 'Observed the student at work.',
                'student_condition' => 'Safe and engaged',
                'company_feedback' => 'Good progress',
                'follow_up_actions' => 'Review next week',
            ],
        ));

        expect($visit->teacher_id)->toBe($this->teacher->id)
            ->and($visit->registration_id)->toBe($this->registration->id)
            ->and($visit->duration_minutes)->toBe(90)
            ->and($visit->method->value)->toBe('site_visit');
    });

    test('2EHSE-FR-SUPV-009 / 2EHSE-NFR-SUPV-003: verifies a visit and refuses double verification', function (): void {
        $visit = MonitoringVisit::factory()->create(['registration_id' => $this->registration->id, 'teacher_id' => $this->teacher->id]);
        $verified = app(VerifyVisitAction::class)->execute($visit, $this->admin);

        expect($verified->fresh()->is_verified)->toBeTrue()
            ->and($verified->fresh()->verified_by)->toBe($this->admin->id)
            ->and($verified->fresh()->verified_at)->not->toBeNull()
            ->and(fn () => app(VerifyVisitAction::class)->execute($verified->fresh(), $this->admin))
            ->toThrow(RejectedException::class);
    });

    test('2EHSE-FR-SUPV-003 / 2EHSE-FR-SUPV-004 / 2EHSE-FR-SUPV-005: creates role-aware logs, reviews feedback, and verifies them', function (): void {
        $teacherLog = app(CreateSupervisionLogAction::class)->execute(new CreateSupervisionLogData(
            $this->teacher->id, $this->registration->id, ['topic' => 'Guidance', 'notes' => 'Teacher guidance'],
        ));
        $supervisorLog = app(CreateSupervisionLogAction::class)->execute(new CreateSupervisionLogData(
            $this->supervisor->id, $this->registration->id, ['topic' => 'Mentoring', 'notes' => 'Floor mentoring'],
        ));
        $reviewed = app(ReviewLogAction::class)->execute(new ReviewLogData(
            $supervisorLog->id, $this->supervisor->id, 'Continue the safety checklist.',
        ));
        expect($teacherLog->status)->toBe(SupervisionLogStatus::COMPLETED)
            ->and($teacherLog->type)->toBe('guidance')
            ->and($reviewed->status)->toBe(SupervisionLogStatus::REVIEWED)
            ->and($reviewed->supervisor_feedback)->toBe('Continue the safety checklist.');

        $verified = app(VerifySupervisionLogAction::class)->execute($reviewed, $this->admin);

        expect($verified->fresh()->status)->toBe(SupervisionLogStatus::VERIFIED)
            ->and($verified->fresh()->verified_by)->toBe($this->admin->id);
    });

    test('2EHSE-FR-SUPV-006: deletes only draft supervision logs', function (): void {
        $draft = SupervisionLog::factory()->create(['registration_id' => $this->registration->id]);
        app(DeleteLogAction::class)->execute($draft);
        expect(SupervisionLog::find($draft->id))->toBeNull();

        $submitted = SupervisionLog::factory()->create([
            'registration_id' => $this->registration->id,
            'status' => SupervisionLogStatus::SUBMITTED,
        ]);
        expect(fn () => app(DeleteLogAction::class)->execute($submitted))->toThrow(RejectedException::class);
    });

    test('2EHSE-FR-SUPV-002 / 2EHSE-FR-SUPV-011: creates a draft supervision log through its DTO action', function (): void {
        $log = app(CreateLogAction::class)->execute(new CreateLogData(
            studentId: $this->student->id,
            registrationId: $this->registration->id,
            data: ['supervisor_id' => $this->supervisor->id, 'topic' => 'Daily check', 'notes' => 'Notes'],
        ));

        expect($log->status)->toBe(SupervisionLogStatus::DRAFT)
            ->and($log->registration_id)->toBe($this->registration->id)
            ->and($log->supervisor_id)->toBe($this->supervisor->id);
    });
});
