<?php

declare(strict_types=1);

use App\Modules\Assignment\Enums\AssignmentStatus;
use App\Modules\Enrollment\Domain\Placement\Enums\PlacementChangeStatus;

describe('J9GBH: PlacementChangeStatus enum', function (): void {
    test('J9GBH-FR-PLACE-013: cases carry the specified backing values', function (): void {
        expect(PlacementChangeStatus::PENDING->value)->toBe('pending');
        expect(PlacementChangeStatus::from('pending'))->toBe(PlacementChangeStatus::PENDING);
        expect(PlacementChangeStatus::APPROVED->value)->toBe('approved');
        expect(PlacementChangeStatus::from('approved'))->toBe(PlacementChangeStatus::APPROVED);
        expect(PlacementChangeStatus::REJECTED->value)->toBe('rejected');
        expect(PlacementChangeStatus::from('rejected'))->toBe(PlacementChangeStatus::REJECTED);
        expect(PlacementChangeStatus::cases())->toHaveCount(3);
        expect(PlacementChangeStatus::tryFrom('no-such-value'))->toBeNull();
    });
    test('J9GBH-FR-PLACE-013: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(PlacementChangeStatus::PENDING->label())->toBe('Pending');
        expect(PlacementChangeStatus::APPROVED->label())->toBe('Approved');
        expect(PlacementChangeStatus::REJECTED->label())->toBe('Rejected');
    });

    test('J9GBH-FR-PLACE-013: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(PlacementChangeStatus::PENDING->label())->toBe('Menunggu');
        expect(PlacementChangeStatus::APPROVED->label())->toBe('Disetujui');
        expect(PlacementChangeStatus::REJECTED->label())->toBe('Ditolak');
    });
    test('J9GBH-FR-PLACE-013: approved and rejected are terminal', function (): void {
        expect(PlacementChangeStatus::APPROVED->isTerminal())->toBeTrue();
        expect(PlacementChangeStatus::REJECTED->isTerminal())->toBeTrue();
        expect(PlacementChangeStatus::PENDING->isTerminal())->toBeFalse();
    });

    test('J9GBH-FR-PLACE-028: transition guards live on the enum via canTransitionTo', function (): void {
        expect(PlacementChangeStatus::PENDING->validTransitions())->toBe([PlacementChangeStatus::APPROVED, PlacementChangeStatus::REJECTED]);
        expect(PlacementChangeStatus::APPROVED->validTransitions())->toBe([]);
        expect(PlacementChangeStatus::REJECTED->validTransitions())->toBe([]);
        expect(PlacementChangeStatus::PENDING->canTransitionTo(PlacementChangeStatus::APPROVED))->toBeTrue();
        expect(PlacementChangeStatus::PENDING->canTransitionTo(PlacementChangeStatus::REJECTED))->toBeTrue();
        expect(PlacementChangeStatus::PENDING->canTransitionTo(PlacementChangeStatus::PENDING))->toBeFalse();
        expect(PlacementChangeStatus::APPROVED->canTransitionTo(PlacementChangeStatus::REJECTED))->toBeFalse();
        expect(PlacementChangeStatus::PENDING->canTransitionTo(AssignmentStatus::PUBLISHED))->toBeFalse();
    });
});
