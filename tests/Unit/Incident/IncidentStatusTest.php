<?php

declare(strict_types=1);

use App\Modules\Assignment\Enums\AssignmentStatus;
use App\Modules\Incident\Domain\IncidentReport\Enums\IncidentStatus;

describe('3RU9S: IncidentStatus enum', function (): void {
    test('3RU9S-FR-INC-008: cases carry the specified backing values', function (): void {
        expect(IncidentStatus::REPORTED->value)->toBe('reported');
        expect(IncidentStatus::from('reported'))->toBe(IncidentStatus::REPORTED);
        expect(IncidentStatus::INVESTIGATING->value)->toBe('investigating');
        expect(IncidentStatus::from('investigating'))->toBe(IncidentStatus::INVESTIGATING);
        expect(IncidentStatus::RESOLVED->value)->toBe('resolved');
        expect(IncidentStatus::from('resolved'))->toBe(IncidentStatus::RESOLVED);
        expect(IncidentStatus::CLOSED->value)->toBe('closed');
        expect(IncidentStatus::from('closed'))->toBe(IncidentStatus::CLOSED);
        expect(IncidentStatus::cases())->toHaveCount(4);
        expect(IncidentStatus::tryFrom('no-such-value'))->toBeNull();
    });
    test('3RU9S-FR-INC-008: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(IncidentStatus::REPORTED->label())->toBe('Reported');
        expect(IncidentStatus::INVESTIGATING->label())->toBe('Investigating');
        expect(IncidentStatus::RESOLVED->label())->toBe('Resolved');
        expect(IncidentStatus::CLOSED->label())->toBe('Closed');
    });

    test('3RU9S-FR-INC-008: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(IncidentStatus::REPORTED->label())->toBe('Dilaporkan');
        expect(IncidentStatus::INVESTIGATING->label())->toBe('Sedang Ditelusuri');
        expect(IncidentStatus::RESOLVED->label())->toBe('Selesai Ditangani');
        expect(IncidentStatus::CLOSED->label())->toBe('Ditutup');
    });
    test('3RU9S-FR-INC-008: closed is the only terminal state', function (): void {
        expect(IncidentStatus::CLOSED->isTerminal())->toBeTrue();
        expect(IncidentStatus::REPORTED->isTerminal())->toBeFalse();
        expect(IncidentStatus::INVESTIGATING->isTerminal())->toBeFalse();
        expect(IncidentStatus::RESOLVED->isTerminal())->toBeFalse();
    });

    test('3RU9S-FR-INC-008: validTransitions pins the explicit transition map', function (): void {
        expect(IncidentStatus::REPORTED->validTransitions())->toBe([IncidentStatus::INVESTIGATING, IncidentStatus::RESOLVED]);
        expect(IncidentStatus::INVESTIGATING->validTransitions())->toBe([IncidentStatus::RESOLVED, IncidentStatus::CLOSED]);
        expect(IncidentStatus::RESOLVED->validTransitions())->toBe([IncidentStatus::CLOSED]);
        expect(IncidentStatus::CLOSED->validTransitions())->toBe([]);
    });

    test('3RU9S-FR-INC-008: canTransitionTo follows the map and rejects foreign enums', function (): void {
        expect(IncidentStatus::REPORTED->canTransitionTo(IncidentStatus::INVESTIGATING))->toBeTrue();
        expect(IncidentStatus::REPORTED->canTransitionTo(IncidentStatus::RESOLVED))->toBeTrue();
        expect(IncidentStatus::REPORTED->canTransitionTo(IncidentStatus::CLOSED))->toBeFalse();
        expect(IncidentStatus::INVESTIGATING->canTransitionTo(IncidentStatus::CLOSED))->toBeTrue();
        expect(IncidentStatus::RESOLVED->canTransitionTo(IncidentStatus::INVESTIGATING))->toBeFalse();
        expect(IncidentStatus::CLOSED->canTransitionTo(IncidentStatus::REPORTED))->toBeFalse();
        expect(IncidentStatus::REPORTED->canTransitionTo(AssignmentStatus::CLOSED))->toBeFalse();
    });
});
