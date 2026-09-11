<?php

declare(strict_types=1);

use App\Modules\Assignment\Enums\AssignmentStatus;
use App\Modules\Journals\Domain\SupervisionLog\Enums\SupervisionLogStatus;

describe('2EHSE: SupervisionLogStatus enum', function (): void {
    test('2EHSE-FR-SUPV-001: cases carry the specified backing values', function (): void {
        expect(SupervisionLogStatus::DRAFT->value)->toBe('draft');
        expect(SupervisionLogStatus::from('draft'))->toBe(SupervisionLogStatus::DRAFT);
        expect(SupervisionLogStatus::SUBMITTED->value)->toBe('submitted');
        expect(SupervisionLogStatus::from('submitted'))->toBe(SupervisionLogStatus::SUBMITTED);
        expect(SupervisionLogStatus::REVIEWED->value)->toBe('reviewed');
        expect(SupervisionLogStatus::from('reviewed'))->toBe(SupervisionLogStatus::REVIEWED);
        expect(SupervisionLogStatus::ACKNOWLEDGED->value)->toBe('acknowledged');
        expect(SupervisionLogStatus::from('acknowledged'))->toBe(SupervisionLogStatus::ACKNOWLEDGED);
        expect(SupervisionLogStatus::VERIFIED->value)->toBe('verified');
        expect(SupervisionLogStatus::from('verified'))->toBe(SupervisionLogStatus::VERIFIED);
        expect(SupervisionLogStatus::COMPLETED->value)->toBe('completed');
        expect(SupervisionLogStatus::from('completed'))->toBe(SupervisionLogStatus::COMPLETED);
        expect(SupervisionLogStatus::cases())->toHaveCount(6);
        expect(SupervisionLogStatus::tryFrom('no-such-value'))->toBeNull();
    });
    test('2EHSE-FR-SUPV-001: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(SupervisionLogStatus::DRAFT->label())->toBe('Draft');
        expect(SupervisionLogStatus::SUBMITTED->label())->toBe('Submitted');
        expect(SupervisionLogStatus::REVIEWED->label())->toBe('Reviewed');
        expect(SupervisionLogStatus::ACKNOWLEDGED->label())->toBe('Acknowledged');
        expect(SupervisionLogStatus::VERIFIED->label())->toBe('Verified');
        expect(SupervisionLogStatus::COMPLETED->label())->toBe('Completed');
    });

    test('2EHSE-FR-SUPV-001: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(SupervisionLogStatus::DRAFT->label())->toBe('Draf');
        expect(SupervisionLogStatus::SUBMITTED->label())->toBe('Dikirim');
        expect(SupervisionLogStatus::REVIEWED->label())->toBe('Ditinjau');
        expect(SupervisionLogStatus::ACKNOWLEDGED->label())->toBe('Dikonfirmasi');
        expect(SupervisionLogStatus::VERIFIED->label())->toBe('Diverifikasi');
        expect(SupervisionLogStatus::COMPLETED->label())->toBe('Selesai');
    });
    test('2EHSE-FR-SUPV-001: draft and submitted are the active states', function (): void {
        expect(SupervisionLogStatus::DRAFT->isActive())->toBeTrue();
        expect(SupervisionLogStatus::SUBMITTED->isActive())->toBeTrue();
        expect(SupervisionLogStatus::REVIEWED->isActive())->toBeFalse();
        expect(SupervisionLogStatus::VERIFIED->isActive())->toBeFalse();
        expect(SupervisionLogStatus::COMPLETED->isActive())->toBeFalse();
    });

    test('2EHSE-FR-SUPV-001: reviewed, acknowledged, and completed are terminal', function (): void {
        expect(SupervisionLogStatus::REVIEWED->isTerminal())->toBeTrue();
        expect(SupervisionLogStatus::ACKNOWLEDGED->isTerminal())->toBeTrue();
        expect(SupervisionLogStatus::COMPLETED->isTerminal())->toBeTrue();
        expect(SupervisionLogStatus::DRAFT->isTerminal())->toBeFalse();
        expect(SupervisionLogStatus::SUBMITTED->isTerminal())->toBeFalse();
        expect(SupervisionLogStatus::VERIFIED->isTerminal())->toBeFalse();
    });

    test('2EHSE-FR-SUPV-001: validTransitions pins the closed transition map', function (): void {
        expect(SupervisionLogStatus::DRAFT->validTransitions())->toBe([SupervisionLogStatus::SUBMITTED]);
        expect(SupervisionLogStatus::SUBMITTED->validTransitions())->toBe([SupervisionLogStatus::REVIEWED, SupervisionLogStatus::DRAFT]);
        expect(SupervisionLogStatus::REVIEWED->validTransitions())->toBe([SupervisionLogStatus::ACKNOWLEDGED, SupervisionLogStatus::VERIFIED]);
        expect(SupervisionLogStatus::ACKNOWLEDGED->validTransitions())->toBe([]);
        expect(SupervisionLogStatus::VERIFIED->validTransitions())->toBe([SupervisionLogStatus::COMPLETED]);
        expect(SupervisionLogStatus::COMPLETED->validTransitions())->toBe([]);
    });

    test('2EHSE-FR-SUPV-001: canTransitionTo follows the map and rejects foreign enums', function (): void {
        expect(SupervisionLogStatus::SUBMITTED->canTransitionTo(SupervisionLogStatus::REVIEWED))->toBeTrue();
        expect(SupervisionLogStatus::SUBMITTED->canTransitionTo(SupervisionLogStatus::DRAFT))->toBeTrue();
        expect(SupervisionLogStatus::REVIEWED->canTransitionTo(SupervisionLogStatus::VERIFIED))->toBeTrue();
        expect(SupervisionLogStatus::VERIFIED->canTransitionTo(SupervisionLogStatus::COMPLETED))->toBeTrue();
        expect(SupervisionLogStatus::DRAFT->canTransitionTo(SupervisionLogStatus::REVIEWED))->toBeFalse();
        expect(SupervisionLogStatus::COMPLETED->canTransitionTo(SupervisionLogStatus::DRAFT))->toBeFalse();
        expect(SupervisionLogStatus::DRAFT->canTransitionTo(AssignmentStatus::DRAFT))->toBeFalse();
    });
});
