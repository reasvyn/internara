<?php

declare(strict_types=1);

use App\Modules\Journal\Domain\Attendance\Entities\AttendanceState;
use App\Modules\Journal\Domain\Attendance\Enums\AttendanceStatus;
use App\Modules\Journal\Domain\Attendance\Models\Attendance;
use App\Modules\Journal\Domain\Logbook\Entities\LogbookState;
use App\Modules\Journal\Domain\Logbook\Enums\LogbookStatus;
use App\Modules\Journal\Domain\Logbook\Models\Logbook;
use App\Modules\Journal\Domain\MonitoringVisit\Entities\VisitState;
use App\Modules\Journal\Domain\MonitoringVisit\Models\MonitoringVisit;
use App\Modules\Journal\Domain\SupervisionLog\Enums\SupervisionLogStatus;
use App\Modules\Journal\Domain\SupervisionLog\Models\SupervisionLog;
use Carbon\Carbon;

test('1KSWL-FR-DAILY-003: logbook state exposes verified and editable transitions', function (): void {
    expect(LogbookState::fromModel(new Logbook(['status' => LogbookStatus::VERIFIED]))->isVerified())->toBeTrue()
        ->and(LogbookState::fromModel(new Logbook(['status' => LogbookStatus::DRAFT]))->canBeEdited())->toBeTrue()
        ->and(LogbookState::fromModel(new Logbook(['status' => LogbookStatus::SUBMITTED]))->canBeEdited())->toBeFalse();
});

test('1KSWL-FR-DAILY-009: attendance state distinguishes clocked-out and excused rows', function (): void {
    $closed = new Attendance(['status' => AttendanceStatus::PRESENT, 'clock_out' => '17:00:00']);
    $excused = new Attendance(['status' => AttendanceStatus::SICK]);

    expect(AttendanceState::fromModel($closed)->hasClockOut())->toBeTrue()
        ->and(AttendanceState::fromModel($excused)->isExcused())->toBeTrue();
});

test('1KSWL-NFR-DAILY-001: verified attendance state retains its signed-off status', function (): void {
    $attendance = new Attendance(['status' => AttendanceStatus::PRESENT, 'clock_out' => '16:00:00']);
    $attendance->is_verified = true;

    expect($attendance->asAttendanceState()->hasClockOut())->toBeTrue()
        ->and($attendance->is_verified)->toBeTrue();
});

test('2EHSE-FR-SUPV-010: an unverified recent visit is editable and deletable', function (): void {
    $visit = new MonitoringVisit(['is_verified' => false, 'visit_date' => Carbon::today()->subDays(7)]);
    $state = VisitState::fromModel($visit);

    expect($state->canBeEdited())->toBeTrue()
        ->and($state->canBeDeleted())->toBeTrue()
        ->and($state->isRecent(Carbon::today()))->toBeTrue();
});

test('2EHSE-FR-SUPV-010 / 2EHSE-FR-SUPV-009: a verified old visit cannot be edited or deleted', function (): void {
    $visit = new MonitoringVisit(['is_verified' => true, 'visit_date' => Carbon::today()->subDays(8)]);
    $state = VisitState::fromModel($visit);

    expect($state->canBeEdited())->toBeFalse()
        ->and($state->canBeDeleted())->toBeFalse()
        ->and($state->isRecent(Carbon::today()))->toBeFalse();
});

test('2EHSE-FR-SUPV-001 / 2EHSE-FR-SUPV-006: supervision state permits only draft editing and submission', function (): void {
    $draft = SupervisionLog::make(['status' => SupervisionLogStatus::DRAFT]);
    $reviewed = SupervisionLog::make(['status' => SupervisionLogStatus::REVIEWED]);

    expect($draft->asSupervisionLogState()->canBeEdited())->toBeTrue()
        ->and($draft->asSupervisionLogState()->canBeSubmitted())->toBeTrue()
        ->and($reviewed->asSupervisionLogState()->needsAcknowledgment())->toBeTrue()
        ->and($reviewed->asSupervisionLogState()->canBeEdited())->toBeFalse();
});

test('2EHSE-FR-SUPV-004 / 2EHSE-FR-SUPV-005: supervision state uses review and verification statuses', function (): void {
    $reviewed = SupervisionLog::make([
        'status' => SupervisionLogStatus::REVIEWED,
        'reviewed_at' => Carbon::now(),
    ]);
    $verified = SupervisionLog::make(['status' => SupervisionLogStatus::VERIFIED]);

    expect($reviewed->asSupervisionLogState()->needsAcknowledgment())->toBeTrue()
        ->and($verified->asSupervisionLogState()->needsAcknowledgment())->toBeFalse();
});

test('2EHSE-FR-SUPV-007: visit state bridges the persisted visit date as Carbon', function (): void {
    $visit = new MonitoringVisit(['is_verified' => false, 'visit_date' => '2026-09-10']);

    expect(VisitState::fromModel($visit)->isRecent(Carbon::parse('2026-09-14')))->toBeTrue();
});
