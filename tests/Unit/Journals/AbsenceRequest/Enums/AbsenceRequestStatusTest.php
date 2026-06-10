<?php

declare(strict_types=1);

use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;

test('absence request status has all cases', function () {
    expect(AbsenceRequestStatus::cases())->toHaveCount(3);
    expect(AbsenceRequestStatus::PENDING->value)->toBe('pending');
    expect(AbsenceRequestStatus::APPROVED->value)->toBe('approved');
    expect(AbsenceRequestStatus::REJECTED->value)->toBe('rejected');
});

test('absence request status labels are non-empty', function () {
    foreach (AbsenceRequestStatus::cases() as $s) {
        expect($s->label())->toBeString()->not->toBeEmpty();
    }
});

test('approved and rejected are processed', function () {
    expect(AbsenceRequestStatus::PENDING->isProcessed())->toBeFalse();
    expect(AbsenceRequestStatus::APPROVED->isProcessed())->toBeTrue();
    expect(AbsenceRequestStatus::REJECTED->isProcessed())->toBeTrue();
});

test('approved and rejected are terminal', function () {
    expect(AbsenceRequestStatus::PENDING->isTerminal())->toBeFalse();
    expect(AbsenceRequestStatus::APPROVED->isTerminal())->toBeTrue();
    expect(AbsenceRequestStatus::REJECTED->isTerminal())->toBeTrue();
});

test('pending can transition to approved or rejected', function () {
    expect(AbsenceRequestStatus::PENDING->validTransitions())->toHaveCount(2);
    expect(AbsenceRequestStatus::PENDING->canTransitionTo(AbsenceRequestStatus::APPROVED))->toBeTrue();
    expect(AbsenceRequestStatus::PENDING->canTransitionTo(AbsenceRequestStatus::REJECTED))->toBeTrue();
});

test('terminal states have no transitions', function () {
    expect(AbsenceRequestStatus::APPROVED->validTransitions())->toBe([]);
    expect(AbsenceRequestStatus::REJECTED->validTransitions())->toBe([]);
    expect(AbsenceRequestStatus::APPROVED->canTransitionTo(AbsenceRequestStatus::PENDING))->toBeFalse();
    expect(AbsenceRequestStatus::REJECTED->canTransitionTo(AbsenceRequestStatus::PENDING))->toBeFalse();
});
