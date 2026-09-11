<?php

declare(strict_types=1);

use App\Modules\Journals\Domain\Attendance\Entities\AttendanceState;
use App\Modules\Journals\Domain\Attendance\Enums\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class AttendanceStateModelDouble extends Model
{
    protected $guarded = [];
}

describe('1KSWL: attendance state', function (): void {
    test('1KSWL-FR-DAILY-008: fromModel bridges status and clock-out without persisting', function (): void {
        $model = new AttendanceStateModelDouble([
            'status' => AttendanceStatus::PRESENT,
            'clock_out' => '2026-04-01 17:00:00',
        ]);

        $state = AttendanceState::fromModel($model);

        expect($state->hasClockOut())->toBeTrue();
        expect($state->isExcused())->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('1KSWL-FR-DAILY-007: fromModel reports an open clock-in when clock-out is missing', function (): void {
        $model = new AttendanceStateModelDouble(['status' => AttendanceStatus::SICK, 'clock_out' => null]);

        $state = AttendanceState::fromModel($model);

        expect($state->hasClockOut())->toBeFalse();
        expect($state->isExcused())->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('1KSWL-FR-DAILY-009: isExcused covers permission and sick only', function (): void {
        expect(AttendanceState::fromArray(['status' => AttendanceStatus::PERMISSION, 'clockOut' => null])->isExcused())->toBeTrue();
        expect(AttendanceState::fromArray(['status' => AttendanceStatus::SICK, 'clockOut' => null])->isExcused())->toBeTrue();
        expect(AttendanceState::fromArray(['status' => AttendanceStatus::PRESENT, 'clockOut' => null])->isExcused())->toBeFalse();
        expect(AttendanceState::fromArray(['status' => AttendanceStatus::ABSENT, 'clockOut' => null])->isExcused())->toBeFalse();
        expect(AttendanceState::fromArray(['status' => null, 'clockOut' => null])->isExcused())->toBeFalse();
    });

    test('1KSWL-FR-DAILY-008: hasClockOut follows the stored timestamp', function (): void {
        $open = AttendanceState::fromArray(['status' => AttendanceStatus::PRESENT, 'clockOut' => null]);
        $closed = AttendanceState::fromArray(['status' => AttendanceStatus::PRESENT, 'clockOut' => Carbon::parse('2026-04-01 17:00:00')]);

        expect($open->hasClockOut())->toBeFalse();
        expect($closed->hasClockOut())->toBeTrue();
    });

    test('1KSWL-FR-DAILY-009: fromArray rejects a missing status', function (): void {
        expect(fn (): AttendanceState => AttendanceState::fromArray(['clockOut' => null]))
            ->toThrow(InvalidArgumentException::class, 'status');
    });
});
