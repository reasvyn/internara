<?php

declare(strict_types=1);

use App\Enrollment\AccountApplication\Enums\AccountApplicationStatus;

describe('isTerminal', function () {
    it('approved is terminal', function () {
        expect(AccountApplicationStatus::APPROVED->isTerminal())->toBeTrue();
    });

    it('rejected is terminal', function () {
        expect(AccountApplicationStatus::REJECTED->isTerminal())->toBeTrue();
    });

    it('pending is not terminal', function () {
        expect(AccountApplicationStatus::PENDING->isTerminal())->toBeFalse();
    });
});

describe('transitions', function () {
    it('pending can transition to approved', function () {
        expect(AccountApplicationStatus::PENDING->canTransitionTo(AccountApplicationStatus::APPROVED))->toBeTrue();
    });

    it('pending can transition to rejected', function () {
        expect(AccountApplicationStatus::PENDING->canTransitionTo(AccountApplicationStatus::REJECTED))->toBeTrue();
    });

    it('approved has no transitions', function () {
        expect(AccountApplicationStatus::APPROVED->validTransitions())->toBe([]);
    });

    it('rejected has no transitions', function () {
        expect(AccountApplicationStatus::REJECTED->validTransitions())->toBe([]);
    });
});

test('returns label for each status', function () {
    foreach (AccountApplicationStatus::cases() as $status) {
        expect($status->label())->toBeString();
    }
});