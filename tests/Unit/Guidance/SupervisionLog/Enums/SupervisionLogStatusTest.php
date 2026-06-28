<?php

declare(strict_types=1);

use App\Guidance\SupervisionLog\Enums\SupervisionLogStatus;

test('supervision log status has all expected cases', function () {
    $cases = SupervisionLogStatus::cases();

    expect($cases)->toHaveCount(4);
    expect(SupervisionLogStatus::DRAFT->value)->toBe('draft');
    expect(SupervisionLogStatus::SUBMITTED->value)->toBe('submitted');
    expect(SupervisionLogStatus::REVIEWED->value)->toBe('reviewed');
    expect(SupervisionLogStatus::ACKNOWLEDGED->value)->toBe('acknowledged');
});

test('supervision log status label returns non-empty string', function () {
    foreach (SupervisionLogStatus::cases() as $status) {
        expect($status->label())->toBeString()->not->toBeEmpty();
    }
});

test('active states are draft and submitted', function () {
    expect(SupervisionLogStatus::DRAFT->isActive())->toBeTrue();
    expect(SupervisionLogStatus::SUBMITTED->isActive())->toBeTrue();
    expect(SupervisionLogStatus::REVIEWED->isActive())->toBeFalse();
    expect(SupervisionLogStatus::ACKNOWLEDGED->isActive())->toBeFalse();
});

test('terminal states are reviewed and acknowledged', function () {
    expect(SupervisionLogStatus::DRAFT->isTerminal())->toBeFalse();
    expect(SupervisionLogStatus::SUBMITTED->isTerminal())->toBeFalse();
    expect(SupervisionLogStatus::REVIEWED->isTerminal())->toBeTrue();
    expect(SupervisionLogStatus::ACKNOWLEDGED->isTerminal())->toBeTrue();
});

test('valid transitions from draft', function () {
    $transitions = SupervisionLogStatus::DRAFT->validTransitions();

    expect($transitions)->toHaveCount(1);
    expect($transitions)->toContain(SupervisionLogStatus::SUBMITTED);
});

test('valid transitions from submitted', function () {
    $transitions = SupervisionLogStatus::SUBMITTED->validTransitions();

    expect($transitions)->toHaveCount(2);
    expect($transitions)->toContain(SupervisionLogStatus::REVIEWED, SupervisionLogStatus::DRAFT);
});

test('reviewed can only go to acknowledged', function () {
    $transitions = SupervisionLogStatus::REVIEWED->validTransitions();

    expect($transitions)->toHaveCount(1);
    expect($transitions)->toContain(SupervisionLogStatus::ACKNOWLEDGED);
});

test('acknowledged has no valid transitions', function () {
    expect(SupervisionLogStatus::ACKNOWLEDGED->validTransitions())->toBe([]);
});

test('can transition to allowed targets', function () {
    expect(SupervisionLogStatus::DRAFT->canTransitionTo(SupervisionLogStatus::SUBMITTED))->toBeTrue();
    expect(SupervisionLogStatus::SUBMITTED->canTransitionTo(SupervisionLogStatus::REVIEWED))->toBeTrue();
    expect(SupervisionLogStatus::SUBMITTED->canTransitionTo(SupervisionLogStatus::DRAFT))->toBeTrue();
    expect(SupervisionLogStatus::REVIEWED->canTransitionTo(SupervisionLogStatus::ACKNOWLEDGED))->toBeTrue();
});

test('cannot transition to disallowed targets', function () {
    expect(SupervisionLogStatus::DRAFT->canTransitionTo(SupervisionLogStatus::REVIEWED))->toBeFalse();
    expect(SupervisionLogStatus::DRAFT->canTransitionTo(SupervisionLogStatus::ACKNOWLEDGED))->toBeFalse();
    expect(SupervisionLogStatus::SUBMITTED->canTransitionTo(SupervisionLogStatus::ACKNOWLEDGED))->toBeFalse();
    expect(SupervisionLogStatus::ACKNOWLEDGED->canTransitionTo(SupervisionLogStatus::DRAFT))->toBeFalse();
    expect(SupervisionLogStatus::REVIEWED->canTransitionTo(SupervisionLogStatus::SUBMITTED))->toBeFalse();
});

test('cannot transition to self', function () {
    expect(SupervisionLogStatus::DRAFT->canTransitionTo(SupervisionLogStatus::DRAFT))->toBeFalse();
    expect(SupervisionLogStatus::ACKNOWLEDGED->canTransitionTo(SupervisionLogStatus::ACKNOWLEDGED))->toBeFalse();
});
