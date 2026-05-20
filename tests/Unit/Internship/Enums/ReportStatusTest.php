<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Contracts\StatusEnum;
use App\Domain\Internship\Enums\ReportStatus;

describe('ReportStatus enum', function () {
    it('implements LabelEnum', function () {
        expect(ReportStatus::class)->toImplement(LabelEnum::class);
    });

    it('implements StatusEnum', function () {
        expect(ReportStatus::class)->toImplement(StatusEnum::class);
    });

    it('has labels', function () {
        expect(ReportStatus::DRAFT->label())->toBe('Draft')
            ->and(ReportStatus::SUBMITTED->label())->toBe('Submitted')
            ->and(ReportStatus::REVISION_REQUIRED->label())->toBe('Revision Required')
            ->and(ReportStatus::APPROVED->label())->toBe('Approved');
    });

    it('detects terminal states', function () {
        expect(ReportStatus::APPROVED->isTerminal())->toBeTrue()
            ->and(ReportStatus::DRAFT->isTerminal())->toBeFalse();
    });

    it('validates transitions', function () {
        expect(ReportStatus::DRAFT->canTransitionTo(ReportStatus::SUBMITTED))->toBeTrue()
            ->and(ReportStatus::SUBMITTED->canTransitionTo(ReportStatus::APPROVED))->toBeTrue()
            ->and(ReportStatus::SUBMITTED->canTransitionTo(ReportStatus::REVISION_REQUIRED))->toBeTrue()
            ->and(ReportStatus::REVISION_REQUIRED->canTransitionTo(ReportStatus::DRAFT))->toBeTrue()
            ->and(ReportStatus::DRAFT->canTransitionTo(ReportStatus::APPROVED))->toBeFalse();
    });
});
