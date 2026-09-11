<?php

declare(strict_types=1);

use App\Modules\Assignment\Enums\AssignmentStatus;
use App\Modules\Partners\Domain\Partnership\Enums\PartnershipStatus;

describe('NTHQA: PartnershipStatus enum', function (): void {
    test('NTHQA-FR-PART-003: cases carry the specified backing values', function (): void {
        expect(PartnershipStatus::ACTIVE->value)->toBe('active');
        expect(PartnershipStatus::from('active'))->toBe(PartnershipStatus::ACTIVE);
        expect(PartnershipStatus::EXPIRED->value)->toBe('expired');
        expect(PartnershipStatus::from('expired'))->toBe(PartnershipStatus::EXPIRED);
        expect(PartnershipStatus::TERMINATED->value)->toBe('terminated');
        expect(PartnershipStatus::from('terminated'))->toBe(PartnershipStatus::TERMINATED);
        expect(PartnershipStatus::cases())->toHaveCount(3);
        expect(PartnershipStatus::tryFrom('no-such-value'))->toBeNull();
    });
    test('NTHQA-FR-PART-003: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(PartnershipStatus::ACTIVE->label())->toBe('Active');
        expect(PartnershipStatus::EXPIRED->label())->toBe('Expired');
        expect(PartnershipStatus::TERMINATED->label())->toBe('Terminated');
    });

    test('NTHQA-FR-PART-003: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(PartnershipStatus::ACTIVE->label())->toBe('Aktif');
        expect(PartnershipStatus::EXPIRED->label())->toBe('Kedaluwarsa');
        expect(PartnershipStatus::TERMINATED->label())->toBe('Dihentikan');
    });
    test('NTHQA-FR-PART-003: expired and terminated are terminal', function (): void {
        expect(PartnershipStatus::EXPIRED->isTerminal())->toBeTrue();
        expect(PartnershipStatus::TERMINATED->isTerminal())->toBeTrue();
        expect(PartnershipStatus::ACTIVE->isTerminal())->toBeFalse();
    });

    test('NTHQA-FR-PART-003: the map is forward-only from active', function (): void {
        expect(PartnershipStatus::ACTIVE->validTransitions())->toBe([PartnershipStatus::EXPIRED, PartnershipStatus::TERMINATED]);
        expect(PartnershipStatus::EXPIRED->validTransitions())->toBe([]);
        expect(PartnershipStatus::TERMINATED->validTransitions())->toBe([]);
        expect(PartnershipStatus::ACTIVE->canTransitionTo(PartnershipStatus::EXPIRED))->toBeTrue();
        expect(PartnershipStatus::ACTIVE->canTransitionTo(PartnershipStatus::TERMINATED))->toBeTrue();
        expect(PartnershipStatus::ACTIVE->canTransitionTo(PartnershipStatus::ACTIVE))->toBeFalse();
        expect(PartnershipStatus::EXPIRED->canTransitionTo(PartnershipStatus::ACTIVE))->toBeFalse();
        expect(PartnershipStatus::ACTIVE->canTransitionTo(AssignmentStatus::CLOSED))->toBeFalse();
    });
});
