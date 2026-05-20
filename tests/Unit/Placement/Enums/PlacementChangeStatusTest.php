<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Contracts\StatusEnum;
use App\Domain\Placement\Enums\PlacementChangeStatus;

describe('PlacementChangeStatus enum', function () {
    it('implements LabelEnum', function () {
        expect(PlacementChangeStatus::class)->toImplement(LabelEnum::class);
    });

    it('implements StatusEnum', function () {
        expect(PlacementChangeStatus::class)->toImplement(StatusEnum::class);
    });

    it('has labels', function () {
        expect(PlacementChangeStatus::PENDING->label())->toBe('Pending')
            ->and(PlacementChangeStatus::APPROVED->label())->toBe('Approved')
            ->and(PlacementChangeStatus::REJECTED->label())->toBe('Rejected');
    });

    it('detects terminal states', function () {
        expect(PlacementChangeStatus::APPROVED->isTerminal())->toBeTrue()
            ->and(PlacementChangeStatus::REJECTED->isTerminal())->toBeTrue()
            ->and(PlacementChangeStatus::PENDING->isTerminal())->toBeFalse();
    });

    it('validates transitions', function () {
        expect(PlacementChangeStatus::PENDING->canTransitionTo(PlacementChangeStatus::APPROVED))->toBeTrue()
            ->and(PlacementChangeStatus::PENDING->canTransitionTo(PlacementChangeStatus::REJECTED))->toBeTrue()
            ->and(PlacementChangeStatus::APPROVED->canTransitionTo(PlacementChangeStatus::PENDING))->toBeFalse();
    });
});
