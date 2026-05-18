<?php

declare(strict_types=1);

use App\Enums\Internship\PlacementChangeStatus;

describe('PlacementChangeStatus', function () {
    it('returns correct labels', function () {
        expect(PlacementChangeStatus::PENDING->label())->toBe('Pending');
        expect(PlacementChangeStatus::APPROVED->label())->toBe('Approved');
        expect(PlacementChangeStatus::REJECTED->label())->toBe('Rejected');
    });

    it('approved and rejected are terminal', function () {
        expect(PlacementChangeStatus::APPROVED->isTerminal())->toBeTrue();
        expect(PlacementChangeStatus::REJECTED->isTerminal())->toBeTrue();
        expect(PlacementChangeStatus::PENDING->isTerminal())->toBeFalse();
    });
});
