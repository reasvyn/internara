<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Contracts\StatusEnum;
use App\Domain\Logbook\Enums\LogbookStatus;

describe('LogbookStatus enum', function () {
    it('implements LabelEnum', function () {
        expect(LogbookStatus::class)->toImplement(LabelEnum::class);
    });

    it('implements StatusEnum', function () {
        expect(LogbookStatus::class)->toImplement(StatusEnum::class);
    });

    it('has labels', function () {
        expect(LogbookStatus::DRAFT->label())->toBe('Draft')
            ->and(LogbookStatus::SUBMITTED->label())->toBe('Submitted')
            ->and(LogbookStatus::VERIFIED->label())->toBe('Verified')
            ->and(LogbookStatus::REVISION_REQUIRED->label())->toBe('Revision Required');
    });

    it('detects finalized', function () {
        expect(LogbookStatus::VERIFIED->isFinalized())->toBeTrue()
            ->and(LogbookStatus::DRAFT->isFinalized())->toBeFalse();
    });

    it('detects requires action', function () {
        expect(LogbookStatus::SUBMITTED->requiresAction())->toBeTrue()
            ->and(LogbookStatus::REVISION_REQUIRED->requiresAction())->toBeTrue()
            ->and(LogbookStatus::DRAFT->requiresAction())->toBeFalse();
    });

    it('detects terminal states', function () {
        expect(LogbookStatus::VERIFIED->isTerminal())->toBeTrue()
            ->and(LogbookStatus::DRAFT->isTerminal())->toBeFalse();
    });

    it('validates transitions', function () {
        expect(LogbookStatus::DRAFT->canTransitionTo(LogbookStatus::SUBMITTED))->toBeTrue()
            ->and(LogbookStatus::SUBMITTED->canTransitionTo(LogbookStatus::VERIFIED))->toBeTrue()
            ->and(LogbookStatus::SUBMITTED->canTransitionTo(LogbookStatus::REVISION_REQUIRED))->toBeTrue()
            ->and(LogbookStatus::REVISION_REQUIRED->canTransitionTo(LogbookStatus::DRAFT))->toBeTrue()
            ->and(LogbookStatus::VERIFIED->canTransitionTo(LogbookStatus::DRAFT))->toBeFalse();
    });
});
