<?php

declare(strict_types=1);

use App\Core\Contracts\StatusEnum;
use App\Incident\IncidentReport\Enums\IncidentStatus;

test('incident status has all expected cases', function () {
    $cases = IncidentStatus::cases();

    expect($cases)->toHaveCount(4);
    expect(IncidentStatus::REPORTED->value)->toBe('reported');
    expect(IncidentStatus::INVESTIGATING->value)->toBe('investigating');
    expect(IncidentStatus::RESOLVED->value)->toBe('resolved');
    expect(IncidentStatus::CLOSED->value)->toBe('closed');
});

test('incident status label returns non-empty string', function () {
    foreach (IncidentStatus::cases() as $status) {
        expect($status->label())->toBeString()->not->toBeEmpty();
    }
});

test('only closed is terminal', function () {
    expect(IncidentStatus::REPORTED->isTerminal())->toBeFalse();
    expect(IncidentStatus::INVESTIGATING->isTerminal())->toBeFalse();
    expect(IncidentStatus::RESOLVED->isTerminal())->toBeFalse();
    expect(IncidentStatus::CLOSED->isTerminal())->toBeTrue();
});

test('valid transitions from reported', function () {
    $transitions = IncidentStatus::REPORTED->validTransitions();

    expect($transitions)->toHaveCount(2);
    expect($transitions)->toContain(IncidentStatus::INVESTIGATING, IncidentStatus::RESOLVED);
});

test('valid transitions from investigating', function () {
    $transitions = IncidentStatus::INVESTIGATING->validTransitions();

    expect($transitions)->toHaveCount(2);
    expect($transitions)->toContain(IncidentStatus::RESOLVED, IncidentStatus::CLOSED);
});

test('valid transitions from resolved', function () {
    $transitions = IncidentStatus::RESOLVED->validTransitions();

    expect($transitions)->toHaveCount(1);
    expect($transitions)->toContain(IncidentStatus::CLOSED);
});

test('closed has no valid transitions', function () {
    expect(IncidentStatus::CLOSED->validTransitions())->toBe([]);
});

test('can transition to allowed targets', function () {
    expect(IncidentStatus::REPORTED->canTransitionTo(IncidentStatus::INVESTIGATING))->toBeTrue();
    expect(IncidentStatus::REPORTED->canTransitionTo(IncidentStatus::RESOLVED))->toBeTrue();
    expect(IncidentStatus::INVESTIGATING->canTransitionTo(IncidentStatus::RESOLVED))->toBeTrue();
    expect(IncidentStatus::INVESTIGATING->canTransitionTo(IncidentStatus::CLOSED))->toBeTrue();
    expect(IncidentStatus::RESOLVED->canTransitionTo(IncidentStatus::CLOSED))->toBeTrue();
});

test('cannot transition to disallowed targets', function () {
    expect(IncidentStatus::REPORTED->canTransitionTo(IncidentStatus::CLOSED))->toBeFalse();
    expect(IncidentStatus::RESOLVED->canTransitionTo(IncidentStatus::INVESTIGATING))->toBeFalse();
    expect(IncidentStatus::CLOSED->canTransitionTo(IncidentStatus::REPORTED))->toBeFalse();
    expect(IncidentStatus::INVESTIGATING->canTransitionTo(IncidentStatus::REPORTED))->toBeFalse();
});

test('cannot transition to self', function () {
    expect(IncidentStatus::REPORTED->canTransitionTo(IncidentStatus::REPORTED))->toBeFalse();
    expect(IncidentStatus::CLOSED->canTransitionTo(IncidentStatus::CLOSED))->toBeFalse();
});

test('returns false for non-status enum target', function () {
    expect(IncidentStatus::REPORTED->canTransitionTo(new class implements StatusEnum
    {
        public function label(): string
        {
            return 'fake';
        }

        public function isTerminal(): bool
        {
            return false;
        }

        public function validTransitions(): array
        {
            return [];
        }

        public function canTransitionTo(StatusEnum $target): bool
        {
            return false;
        }
    }))->toBeFalse();
});
