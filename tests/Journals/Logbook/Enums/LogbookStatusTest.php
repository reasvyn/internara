<?php

declare(strict_types=1);

use App\Journals\Logbook\Enums\LogbookStatus;

test('logbook status has all cases', function () {
    expect(LogbookStatus::cases())->toHaveCount(4);
    expect(LogbookStatus::DRAFT->value)->toBe('draft');
    expect(LogbookStatus::SUBMITTED->value)->toBe('submitted');
    expect(LogbookStatus::VERIFIED->value)->toBe('verified');
    expect(LogbookStatus::REVISION_REQUIRED->value)->toBe('revision_required');
});

test('logbook status labels are non-empty', function () {
    foreach (LogbookStatus::cases() as $s) {
        expect($s->label())->toBeString()->not->toBeEmpty();
    }
});

test('only verified is finalized', function () {
    expect(LogbookStatus::VERIFIED->isFinalized())->toBeTrue();
    expect(LogbookStatus::DRAFT->isFinalized())->toBeFalse();
    expect(LogbookStatus::SUBMITTED->isFinalized())->toBeFalse();
    expect(LogbookStatus::REVISION_REQUIRED->isFinalized())->toBeFalse();
});

test('submitted and revision required require action', function () {
    expect(LogbookStatus::SUBMITTED->requiresAction())->toBeTrue();
    expect(LogbookStatus::REVISION_REQUIRED->requiresAction())->toBeTrue();
    expect(LogbookStatus::DRAFT->requiresAction())->toBeFalse();
    expect(LogbookStatus::VERIFIED->requiresAction())->toBeFalse();
});

test('only verified is terminal', function () {
    expect(LogbookStatus::VERIFIED->isTerminal())->toBeTrue();
    expect(LogbookStatus::DRAFT->isTerminal())->toBeFalse();
    expect(LogbookStatus::SUBMITTED->isTerminal())->toBeFalse();
    expect(LogbookStatus::REVISION_REQUIRED->isTerminal())->toBeFalse();
});

test('valid transitions', function () {
    expect(LogbookStatus::DRAFT->validTransitions())->toContain(LogbookStatus::SUBMITTED);
    expect(LogbookStatus::SUBMITTED->validTransitions())->toContain(LogbookStatus::VERIFIED, LogbookStatus::REVISION_REQUIRED);
    expect(LogbookStatus::REVISION_REQUIRED->validTransitions())->toContain(LogbookStatus::DRAFT);
    expect(LogbookStatus::VERIFIED->validTransitions())->toBe([]);
});

test('can transition correctly', function () {
    expect(LogbookStatus::DRAFT->canTransitionTo(LogbookStatus::SUBMITTED))->toBeTrue();
    expect(LogbookStatus::SUBMITTED->canTransitionTo(LogbookStatus::VERIFIED))->toBeTrue();
    expect(LogbookStatus::SUBMITTED->canTransitionTo(LogbookStatus::REVISION_REQUIRED))->toBeTrue();
    expect(LogbookStatus::REVISION_REQUIRED->canTransitionTo(LogbookStatus::DRAFT))->toBeTrue();
    expect(LogbookStatus::DRAFT->canTransitionTo(LogbookStatus::VERIFIED))->toBeFalse();
    expect(LogbookStatus::VERIFIED->canTransitionTo(LogbookStatus::DRAFT))->toBeFalse();
});
