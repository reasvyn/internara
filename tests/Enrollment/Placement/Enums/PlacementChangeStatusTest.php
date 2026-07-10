<?php

declare(strict_types=1);

use App\Enrollment\Placement\Enums\PlacementChangeStatus;

describe('isTerminal', function () {
    it('approved is terminal', function () {
        expect(PlacementChangeStatus::APPROVED->isTerminal())->toBeTrue();
    });
    it('rejected is terminal', function () {
        expect(PlacementChangeStatus::REJECTED->isTerminal())->toBeTrue();
    });
    it('pending is not terminal', function () {
        expect(PlacementChangeStatus::PENDING->isTerminal())->toBeFalse();
    });
});

describe('transitions', function () {
    it('pending to approved', function () {
        expect(PlacementChangeStatus::PENDING->canTransitionTo(PlacementChangeStatus::APPROVED))->toBeTrue();
    });
    it('pending to rejected', function () {
        expect(PlacementChangeStatus::PENDING->canTransitionTo(PlacementChangeStatus::REJECTED))->toBeTrue();
    });
    it('approved has no transitions', function () {
        expect(PlacementChangeStatus::APPROVED->validTransitions())->toBe([]);
    });
});

test('label returns string', function () {
    foreach (PlacementChangeStatus::cases() as $s) {
        expect($s->label())->toBeString();
    }
});
