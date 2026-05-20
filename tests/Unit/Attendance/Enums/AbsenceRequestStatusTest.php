<?php

declare(strict_types=1);

use App\Domain\Attendance\Enums\AbsenceRequestStatus;
use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Contracts\StatusEnum;

describe('AbsenceRequestStatus enum', function () {
    it('implements LabelEnum', function () {
        expect(AbsenceRequestStatus::class)->toImplement(LabelEnum::class);
    });

    it('implements StatusEnum', function () {
        expect(AbsenceRequestStatus::class)->toImplement(StatusEnum::class);
    });

    it('has labels', function () {
        expect(AbsenceRequestStatus::PENDING->label())->toBe('Pending')
            ->and(AbsenceRequestStatus::APPROVED->label())->toBe('Approved')
            ->and(AbsenceRequestStatus::REJECTED->label())->toBe('Rejected');
    });

    it('detects processed', function () {
        expect(AbsenceRequestStatus::APPROVED->isProcessed())->toBeTrue()
            ->and(AbsenceRequestStatus::REJECTED->isProcessed())->toBeTrue()
            ->and(AbsenceRequestStatus::PENDING->isProcessed())->toBeFalse();
    });

    it('detects terminal states', function () {
        expect(AbsenceRequestStatus::APPROVED->isTerminal())->toBeTrue()
            ->and(AbsenceRequestStatus::PENDING->isTerminal())->toBeFalse();
    });
});
