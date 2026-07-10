<?php

declare(strict_types=1);

use App\Assignment\Enums\AssignmentStatus;

test('assignment status has all cases', function () {
    expect(AssignmentStatus::cases())->toHaveCount(3);
    expect(AssignmentStatus::DRAFT->value)->toBe('draft');
    expect(AssignmentStatus::PUBLISHED->value)->toBe('published');
    expect(AssignmentStatus::CLOSED->value)->toBe('closed');
});

test('assignment status labels are non-empty', function () {
    foreach (AssignmentStatus::cases() as $s) {
        expect($s->label())->toBeString()->not->toBeEmpty();
    }
});

test('only published is active', function () {
    expect(AssignmentStatus::PUBLISHED->isActive())->toBeTrue();
    expect(AssignmentStatus::DRAFT->isActive())->toBeFalse();
    expect(AssignmentStatus::CLOSED->isActive())->toBeFalse();
});

test('only closed is terminal', function () {
    expect(AssignmentStatus::CLOSED->isTerminal())->toBeTrue();
    expect(AssignmentStatus::DRAFT->isTerminal())->toBeFalse();
    expect(AssignmentStatus::PUBLISHED->isTerminal())->toBeFalse();
});

test('draft can transition to published or closed', function () {
    expect(AssignmentStatus::DRAFT->validTransitions())->toContain(AssignmentStatus::PUBLISHED, AssignmentStatus::CLOSED);
    expect(AssignmentStatus::DRAFT->canTransitionTo(AssignmentStatus::PUBLISHED))->toBeTrue();
    expect(AssignmentStatus::DRAFT->canTransitionTo(AssignmentStatus::CLOSED))->toBeTrue();
});

test('published can only close', function () {
    expect(AssignmentStatus::PUBLISHED->validTransitions())->toContain(AssignmentStatus::CLOSED);
    expect(AssignmentStatus::PUBLISHED->canTransitionTo(AssignmentStatus::CLOSED))->toBeTrue();
    expect(AssignmentStatus::PUBLISHED->canTransitionTo(AssignmentStatus::DRAFT))->toBeFalse();
});

test('closed cannot transition', function () {
    expect(AssignmentStatus::CLOSED->validTransitions())->toBe([]);
});
