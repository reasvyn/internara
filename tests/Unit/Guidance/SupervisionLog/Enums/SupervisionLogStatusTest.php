<?php

declare(strict_types=1);

use App\Guidance\SupervisionLog\Enums\SupervisionLogStatus;

test('supervision log status has all expected cases', function () {
    $cases = SupervisionLogStatus::cases();

    expect($cases)->toHaveCount(6);
    expect(SupervisionLogStatus::PENDING->value)->toBe('pending');
    expect(SupervisionLogStatus::IN_PROGRESS->value)->toBe('in_progress');
    expect(SupervisionLogStatus::SUBMITTED->value)->toBe('submitted');
    expect(SupervisionLogStatus::VERIFIED->value)->toBe('verified');
    expect(SupervisionLogStatus::COMPLETED->value)->toBe('completed');
    expect(SupervisionLogStatus::CANCELLED->value)->toBe('cancelled');
});

test('supervision log status label returns non-empty string', function () {
    foreach (SupervisionLogStatus::cases() as $status) {
        expect($status->label())->toBeString()->not->toBeEmpty();
    }
});

test('active states are pending, in progress, and submitted', function () {
    expect(SupervisionLogStatus::PENDING->isActive())->toBeTrue();
    expect(SupervisionLogStatus::IN_PROGRESS->isActive())->toBeTrue();
    expect(SupervisionLogStatus::SUBMITTED->isActive())->toBeTrue();
    expect(SupervisionLogStatus::VERIFIED->isActive())->toBeFalse();
    expect(SupervisionLogStatus::COMPLETED->isActive())->toBeFalse();
    expect(SupervisionLogStatus::CANCELLED->isActive())->toBeFalse();
});

test('terminal states are verified, completed, and cancelled', function () {
    expect(SupervisionLogStatus::PENDING->isTerminal())->toBeFalse();
    expect(SupervisionLogStatus::IN_PROGRESS->isTerminal())->toBeFalse();
    expect(SupervisionLogStatus::SUBMITTED->isTerminal())->toBeFalse();
    expect(SupervisionLogStatus::VERIFIED->isTerminal())->toBeTrue();
    expect(SupervisionLogStatus::COMPLETED->isTerminal())->toBeTrue();
    expect(SupervisionLogStatus::CANCELLED->isTerminal())->toBeTrue();
});

test('valid transitions from pending', function () {
    $transitions = SupervisionLogStatus::PENDING->validTransitions();

    expect($transitions)->toHaveCount(2);
    expect($transitions)->toContain(SupervisionLogStatus::IN_PROGRESS, SupervisionLogStatus::CANCELLED);
});

test('valid transitions from in progress', function () {
    $transitions = SupervisionLogStatus::IN_PROGRESS->validTransitions();

    expect($transitions)->toHaveCount(2);
    expect($transitions)->toContain(SupervisionLogStatus::SUBMITTED, SupervisionLogStatus::CANCELLED);
});

test('valid transitions from submitted', function () {
    $transitions = SupervisionLogStatus::SUBMITTED->validTransitions();

    expect($transitions)->toHaveCount(3);
    expect($transitions)->toContain(SupervisionLogStatus::VERIFIED, SupervisionLogStatus::COMPLETED, SupervisionLogStatus::CANCELLED);
});

test('verified can only go to completed', function () {
    $transitions = SupervisionLogStatus::VERIFIED->validTransitions();

    expect($transitions)->toHaveCount(1);
    expect($transitions)->toContain(SupervisionLogStatus::COMPLETED);
});

test('completed has no valid transitions', function () {
    expect(SupervisionLogStatus::COMPLETED->validTransitions())->toBe([]);
});

test('cancelled has no valid transitions', function () {
    expect(SupervisionLogStatus::CANCELLED->validTransitions())->toBe([]);
});

test('can transition to allowed targets', function () {
    expect(SupervisionLogStatus::PENDING->canTransitionTo(SupervisionLogStatus::IN_PROGRESS))->toBeTrue();
    expect(SupervisionLogStatus::PENDING->canTransitionTo(SupervisionLogStatus::CANCELLED))->toBeTrue();
    expect(SupervisionLogStatus::IN_PROGRESS->canTransitionTo(SupervisionLogStatus::SUBMITTED))->toBeTrue();
    expect(SupervisionLogStatus::SUBMITTED->canTransitionTo(SupervisionLogStatus::VERIFIED))->toBeTrue();
    expect(SupervisionLogStatus::SUBMITTED->canTransitionTo(SupervisionLogStatus::COMPLETED))->toBeTrue();
    expect(SupervisionLogStatus::VERIFIED->canTransitionTo(SupervisionLogStatus::COMPLETED))->toBeTrue();
});

test('cannot transition to disallowed targets', function () {
    expect(SupervisionLogStatus::PENDING->canTransitionTo(SupervisionLogStatus::VERIFIED))->toBeFalse();
    expect(SupervisionLogStatus::PENDING->canTransitionTo(SupervisionLogStatus::COMPLETED))->toBeFalse();
    expect(SupervisionLogStatus::IN_PROGRESS->canTransitionTo(SupervisionLogStatus::VERIFIED))->toBeFalse();
    expect(SupervisionLogStatus::COMPLETED->canTransitionTo(SupervisionLogStatus::PENDING))->toBeFalse();
    expect(SupervisionLogStatus::CANCELLED->canTransitionTo(SupervisionLogStatus::PENDING))->toBeFalse();
    expect(SupervisionLogStatus::VERIFIED->canTransitionTo(SupervisionLogStatus::SUBMITTED))->toBeFalse();
});

test('cannot transition to self', function () {
    expect(SupervisionLogStatus::PENDING->canTransitionTo(SupervisionLogStatus::PENDING))->toBeFalse();
    expect(SupervisionLogStatus::COMPLETED->canTransitionTo(SupervisionLogStatus::COMPLETED))->toBeFalse();
});
