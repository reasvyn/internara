<?php

declare(strict_types=1);

use App\Reports\Report\Enums\ReportStatus;

test('report status has all required cases', function () {
    expect(ReportStatus::cases())->toHaveCount(4);
    expect(ReportStatus::DRAFT->value)->toBe('draft');
    expect(ReportStatus::SUBMITTED->value)->toBe('submitted');
    expect(ReportStatus::APPROVED->value)->toBe('approved');
    expect(ReportStatus::FINALIZED->value)->toBe('finalized');
});

test('report status labels are non-empty', function () {
    foreach (ReportStatus::cases() as $s) {
        expect($s->label())->toBeString()->not->toBeEmpty();
    }
});

test('only finalized is terminal', function () {
    expect(ReportStatus::DRAFT->isTerminal())->toBeFalse();
    expect(ReportStatus::FINALIZED->isTerminal())->toBeTrue();
});

test('draft can transition to submitted', function () {
    expect(ReportStatus::DRAFT->validTransitions())->toContain(ReportStatus::SUBMITTED);
    expect(ReportStatus::DRAFT->canTransitionTo(ReportStatus::SUBMITTED))->toBeTrue();
});

test('submitted can transition to approved', function () {
    expect(ReportStatus::SUBMITTED->validTransitions())->toContain(ReportStatus::APPROVED);
});

test('approved can transition to finalized', function () {
    expect(ReportStatus::APPROVED->validTransitions())->toContain(ReportStatus::FINALIZED);
});

test('finalized cannot transition', function () {
    expect(ReportStatus::FINALIZED->validTransitions())->toBe([]);
    expect(ReportStatus::FINALIZED->canTransitionTo(ReportStatus::DRAFT))->toBeFalse();
});
