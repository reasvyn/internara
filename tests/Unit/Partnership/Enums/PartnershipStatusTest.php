<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Contracts\StatusEnum;
use App\Domain\Partnership\Enums\PartnershipStatus;

describe('PartnershipStatus', function () {
    it('is string-backed', function () {
        expect(PartnershipStatus::ACTIVE->value)->toBe('active');
    });

    it('implements LabelEnum', function () {
        expect(PartnershipStatus::ACTIVE)->toBeInstanceOf(LabelEnum::class);
    });

    it('implements StatusEnum', function () {
        expect(PartnershipStatus::ACTIVE)->toBeInstanceOf(StatusEnum::class);
    });

    it('active can transition to expired', function () {
        expect(PartnershipStatus::ACTIVE->canTransitionTo(PartnershipStatus::EXPIRED))->toBeTrue();
    });

    it('active can transition to terminated', function () {
        expect(PartnershipStatus::ACTIVE->canTransitionTo(PartnershipStatus::TERMINATED))->toBeTrue();
    });

    it('expired cannot transition', function () {
        expect(PartnershipStatus::EXPIRED->canTransitionTo(PartnershipStatus::ACTIVE))->toBeFalse()
            ->and(PartnershipStatus::EXPIRED->canTransitionTo(PartnershipStatus::TERMINATED))->toBeFalse();
    });

    it('terminated cannot transition', function () {
        expect(PartnershipStatus::TERMINATED->canTransitionTo(PartnershipStatus::ACTIVE))->toBeFalse()
            ->and(PartnershipStatus::TERMINATED->canTransitionTo(PartnershipStatus::EXPIRED))->toBeFalse();
    });

    it('active is not terminal', function () {
        expect(PartnershipStatus::ACTIVE->isTerminal())->toBeFalse();
    });

    it('expired is terminal', function () {
        expect(PartnershipStatus::EXPIRED->isTerminal())->toBeTrue();
    });

    it('terminated is terminal', function () {
        expect(PartnershipStatus::TERMINATED->isTerminal())->toBeTrue();
    });

    it('active has valid transitions', function () {
        expect(PartnershipStatus::ACTIVE->validTransitions())->toContain(PartnershipStatus::EXPIRED, PartnershipStatus::TERMINATED);
    });

    it('terminal states have empty transitions', function () {
        expect(PartnershipStatus::EXPIRED->validTransitions())->toBe([])
            ->and(PartnershipStatus::TERMINATED->validTransitions())->toBe([]);
    });

    it('rejects non-self comparison in canTransitionTo', function () {
        expect(PartnershipStatus::ACTIVE->canTransitionTo(new class implements StatusEnum
        {
            public function label(): string
            {
                return '';
            }

            public function isTerminal(): bool
            {
                return true;
            }

            public function validTransitions(): array
            {
                return [];
            }

            public function canTransitionTo(StatusEnum $target): bool
            {
                return true;
            }
        }))->toBeFalse();
    });
});
