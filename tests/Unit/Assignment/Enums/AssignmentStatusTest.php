<?php

declare(strict_types=1);

use App\Domain\Assignment\Enums\AssignmentStatus;
use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Contracts\StatusEnum;

describe('AssignmentStatus enum', function () {
    it('implements LabelEnum', function () {
        expect(AssignmentStatus::class)->toImplement(LabelEnum::class);
    });

    it('implements StatusEnum', function () {
        expect(AssignmentStatus::class)->toImplement(StatusEnum::class);
    });

    it('has labels', function () {
        expect(AssignmentStatus::DRAFT->label())->toBe('Draft')
            ->and(AssignmentStatus::PUBLISHED->label())->toBe('Published')
            ->and(AssignmentStatus::CLOSED->label())->toBe('Closed');
    });

    it('detects active', function () {
        expect(AssignmentStatus::PUBLISHED->isActive())->toBeTrue()
            ->and(AssignmentStatus::DRAFT->isActive())->toBeFalse();
    });

    it('detects terminal', function () {
        expect(AssignmentStatus::CLOSED->isTerminal())->toBeTrue()
            ->and(AssignmentStatus::DRAFT->isTerminal())->toBeFalse();
    });

    it('validates transitions', function () {
        expect(AssignmentStatus::DRAFT->canTransitionTo(AssignmentStatus::PUBLISHED))->toBeTrue()
            ->and(AssignmentStatus::DRAFT->canTransitionTo(AssignmentStatus::CLOSED))->toBeTrue()
            ->and(AssignmentStatus::PUBLISHED->canTransitionTo(AssignmentStatus::DRAFT))->toBeFalse();
    });
});
