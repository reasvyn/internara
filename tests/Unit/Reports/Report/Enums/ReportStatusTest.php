<?php

declare(strict_types=1);

use App\Reports\Report\Enums\ReportStatus;

test('report status has required cases', function () {
    expect(ReportStatus::cases())->toHaveCount(2);
    expect(ReportStatus::DRAFT->value)->toBe('draft');
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

test('draft can transition to finalized', function () {
    expect(ReportStatus::DRAFT->validTransitions())->toContain(ReportStatus::FINALIZED);
    expect(ReportStatus::DRAFT->canTransitionTo(ReportStatus::FINALIZED))->toBeTrue();
});

test('finalized cannot transition', function () {
    expect(ReportStatus::FINALIZED->validTransitions())->toBe([]);
    expect(ReportStatus::FINALIZED->canTransitionTo(ReportStatus::DRAFT))->toBeFalse();
});
