<?php

declare(strict_types=1);

use App\Enums\Incident\IncidentStatus;

describe('IncidentStatus', function () {
    describe('label', function () {
        it('returns correct labels', function () {
            expect(IncidentStatus::REPORTED->label())->toBe('Reported');
            expect(IncidentStatus::INVESTIGATING->label())->toBe('Investigating');
            expect(IncidentStatus::RESOLVED->label())->toBe('Resolved');
            expect(IncidentStatus::CLOSED->label())->toBe('Closed');
        });
    });

    describe('isTerminal', function () {
        it('closed is terminal', function () {
            expect(IncidentStatus::CLOSED->isTerminal())->toBeTrue();
        });

        it('reported is not terminal', function () {
            expect(IncidentStatus::REPORTED->isTerminal())->toBeFalse();
        });
    });

    describe('canTransitionTo', function () {
        it('reported can be investigated or resolved', function () {
            expect(IncidentStatus::REPORTED->canTransitionTo(IncidentStatus::INVESTIGATING))->toBeTrue();
            expect(IncidentStatus::REPORTED->canTransitionTo(IncidentStatus::RESOLVED))->toBeTrue();
        });

        it('reported cannot be closed directly', function () {
            expect(IncidentStatus::REPORTED->canTransitionTo(IncidentStatus::CLOSED))->toBeFalse();
        });

        it('closed has no transitions', function () {
            foreach (IncidentStatus::cases() as $target) {
                expect(IncidentStatus::CLOSED->canTransitionTo($target))->toBeFalse();
            }
        });
    });
});
