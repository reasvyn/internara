<?php

declare(strict_types=1);

use App\Modules\Program\Domain\Internship\Entities\InternshipPeriod;
use App\Modules\Program\Domain\Internship\Enums\InternshipStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class InternshipPeriodModelDouble extends Model
{
    protected $guarded = [];
}

describe('7C5WM: internship period', function (): void {
    $window = fn (): array => [
        'status' => InternshipStatus::ACTIVE,
        'registrationStartDate' => Carbon::parse('2026-01-01 00:00:00'),
        'registrationEndDate' => Carbon::parse('2026-01-31 23:59:59'),
        'academicYearStart' => Carbon::parse('2025-07-01 00:00:00'),
        'academicYearEnd' => Carbon::parse('2026-06-30 23:59:59'),
    ];

    test('7C5WM-FR-LIFE-021: fromModel bridges status, window, and academic year without persisting', function (): void {
        $academicYear = new InternshipPeriodModelDouble([
            'start_date' => Carbon::parse('2025-07-01 00:00:00'),
            'end_date' => Carbon::parse('2026-06-30 23:59:59'),
        ]);
        $model = new InternshipPeriodModelDouble([
            'status' => InternshipStatus::PUBLISHED,
            'registration_start_date' => Carbon::parse('2026-01-01 00:00:00'),
            'registration_end_date' => Carbon::parse('2026-01-31 23:59:59'),
        ]);
        $model->setRelation('academicYear', $academicYear);

        $period = InternshipPeriod::fromModel($model);

        expect($period->hasAcademicYear())->toBeTrue();
        expect($period->isAcceptingRegistrations(Carbon::parse('2026-01-15 12:00:00')))->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('7C5WM-FR-LIFE-021: fromModel tolerates a missing academic year', function (): void {
        $model = new InternshipPeriodModelDouble(['status' => InternshipStatus::DRAFT]);

        $period = InternshipPeriod::fromModel($model);

        expect($period->hasAcademicYear())->toBeFalse();
        expect($period->isWithinAcademicYear(Carbon::parse('2026-01-15 00:00:00')))->toBeTrue();
        expect($period->datesSpanOutsideAcademicYear(Carbon::parse('2026-01-01 00:00:00'), Carbon::parse('2026-02-01 00:00:00')))->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('7C5WM-FR-LIFE-022: isAcceptingRegistrations combines status and date window', function () use ($window): void {
        $open = InternshipPeriod::fromArray($window());
        $draft = InternshipPeriod::fromArray([...$window(), 'status' => InternshipStatus::DRAFT]);
        $closed = InternshipPeriod::fromArray([...$window(), 'status' => InternshipStatus::COMPLETED]);

        expect($open->isAcceptingRegistrations(Carbon::parse('2026-01-15 12:00:00')))->toBeTrue();
        expect($open->isAcceptingRegistrations(Carbon::parse('2025-12-31 23:59:59')))->toBeFalse();
        expect($open->isAcceptingRegistrations(Carbon::parse('2026-02-01 00:00:00')))->toBeFalse();
        expect($draft->isAcceptingRegistrations(Carbon::parse('2026-01-15 12:00:00')))->toBeFalse();
        expect($closed->isAcceptingRegistrations(Carbon::parse('2026-01-15 12:00:00')))->toBeFalse();
    });

    test('7C5WM-FR-LIFE-023: isRegistrationWindowOpen evaluates dates alone', function () use ($window): void {
        $draft = InternshipPeriod::fromArray([...$window(), 'status' => InternshipStatus::DRAFT]);

        expect($draft->isRegistrationWindowOpen(Carbon::parse('2026-01-15 12:00:00')))->toBeTrue();
        expect($draft->isRegistrationWindowOpen(Carbon::parse('2025-12-31 00:00:00')))->toBeFalse();
        expect($draft->isRegistrationWindowOpen(Carbon::parse('2026-02-01 00:00:00')))->toBeFalse();
    });

    test('7C5WM-FR-LIFE-024: before and after window distinguish early from late attempts', function () use ($window): void {
        $period = InternshipPeriod::fromArray($window());

        expect($period->isBeforeRegistrationWindow(Carbon::parse('2025-12-31 00:00:00')))->toBeTrue();
        expect($period->isBeforeRegistrationWindow(Carbon::parse('2026-01-15 00:00:00')))->toBeFalse();
        expect($period->isAfterRegistrationWindow(Carbon::parse('2026-02-01 00:00:00')))->toBeTrue();
        expect($period->isAfterRegistrationWindow(Carbon::parse('2026-01-15 00:00:00')))->toBeFalse();
    });

    test('7C5WM-FR-LIFE-025: academic-year bounds validate program dates', function () use ($window): void {
        $period = InternshipPeriod::fromArray($window());

        expect($period->hasAcademicYear())->toBeTrue();
        expect($period->isWithinAcademicYear(Carbon::parse('2026-01-15 00:00:00')))->toBeTrue();
        expect($period->isWithinAcademicYear(Carbon::parse('2026-07-01 00:00:00')))->toBeFalse();
        expect($period->datesSpanOutsideAcademicYear(Carbon::parse('2026-01-01 00:00:00'), Carbon::parse('2026-02-01 00:00:00')))->toBeFalse();
        expect($period->datesSpanOutsideAcademicYear(Carbon::parse('2025-06-01 00:00:00'), Carbon::parse('2026-02-01 00:00:00')))->toBeTrue();
        expect($period->datesSpanOutsideAcademicYear(Carbon::parse('2026-01-01 00:00:00'), Carbon::parse('2026-07-01 00:00:00')))->toBeTrue();
    });

    test('7C5WM-FR-LIFE-021: fromArray applies null defaults and rejects a missing status', function (): void {
        $period = InternshipPeriod::fromArray(['status' => null]);

        expect($period->hasAcademicYear())->toBeFalse();
        expect($period->isAcceptingRegistrations(Carbon::parse('2026-01-15 00:00:00')))->toBeFalse();
        expect($period->isRegistrationWindowOpen(Carbon::parse('2026-01-15 00:00:00')))->toBeTrue();

        expect(fn (): InternshipPeriod => InternshipPeriod::fromArray([]))
            ->toThrow(InvalidArgumentException::class, 'status');
    });
});
