<?php

declare(strict_types=1);

use App\Journals\SupervisionLog\Enums\SupervisionLogStatus;

test('supervision log status has all expected cases', function () {
    $cases = SupervisionLogStatus::cases();

    expect($cases)->toHaveCount(6);
    expect(SupervisionLogStatus::DRAFT->value)->toBe('draft');
    expect(SupervisionLogStatus::SUBMITTED->value)->toBe('submitted');
    expect(SupervisionLogStatus::REVIEWED->value)->toBe('reviewed');
    expect(SupervisionLogStatus::ACKNOWLEDGED->value)->toBe('acknowledged');
    expect(SupervisionLogStatus::VERIFIED->value)->toBe('verified');
    expect(SupervisionLogStatus::COMPLETED->value)->toBe('completed');
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
    expect(SupervisionLogStatus::VERIFIED->isActive())->toBeFalse();
    expect(SupervisionLogStatus::COMPLETED->isActive())->toBeFalse();
});

test('terminal states are reviewed, acknowledged, and completed', function () {
    expect(SupervisionLogStatus::DRAFT->isTerminal())->toBeFalse();
    expect(SupervisionLogStatus::SUBMITTED->isTerminal())->toBeFalse();
    expect(SupervisionLogStatus::REVIEWED->isTerminal())->toBeTrue();
    expect(SupervisionLogStatus::ACKNOWLEDGED->isTerminal())->toBeTrue();
    expect(SupervisionLogStatus::VERIFIED->isTerminal())->toBeFalse();
    expect(SupervisionLogStatus::COMPLETED->isTerminal())->toBeTrue();
});

test('valid transitions from draft', function () {
    expect(SupervisionLogStatus::DRAFT->validTransitions())->toHaveCount(1);
    expect(SupervisionLogStatus::DRAFT->validTransitions())->toContain(SupervisionLogStatus::SUBMITTED);
});

test('valid transitions from submitted', function () {
    $transitions = SupervisionLogStatus::SUBMITTED->validTransitions();
    expect($transitions)->toHaveCount(2);
    expect($transitions)->toContain(SupervisionLogStatus::REVIEWED, SupervisionLogStatus::DRAFT);
});

test('reviewed goes to acknowledged or verified', function () {
    $transitions = SupervisionLogStatus::REVIEWED->validTransitions();
    expect($transitions)->toHaveCount(2);
    expect($transitions)->toContain(SupervisionLogStatus::ACKNOWLEDGED, SupervisionLogStatus::VERIFIED);
});

test('acknowledged has no valid transitions', function () {
    expect(SupervisionLogStatus::ACKNOWLEDGED->validTransitions())->toBe([]);
});

test('verified goes to completed', function () {
    expect(SupervisionLogStatus::VERIFIED->validTransitions())->toHaveCount(1);
    expect(SupervisionLogStatus::VERIFIED->validTransitions())->toContain(SupervisionLogStatus::COMPLETED);
});

test('completed has no valid transitions', function () {
    expect(SupervisionLogStatus::COMPLETED->validTransitions())->toBe([]);
});
