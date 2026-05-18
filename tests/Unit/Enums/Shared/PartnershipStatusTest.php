<?php

declare(strict_types=1);

use App\Enums\Shared\PartnershipStatus;

describe('PartnershipStatus', function () {
    describe('label', function () {
        it('returns correct label for active', function () {
            expect(PartnershipStatus::ACTIVE->label())->toBe('Active');
        });

        it('returns correct label for expired', function () {
            expect(PartnershipStatus::EXPIRED->label())->toBe('Expired');
        });

        it('returns correct label for terminated', function () {
            expect(PartnershipStatus::TERMINATED->label())->toBe('Terminated');
        });
    });

    describe('isTerminal', function () {
        it('returns true for expired', function () {
            expect(PartnershipStatus::EXPIRED->isTerminal())->toBeTrue();
        });

        it('returns true for terminated', function () {
            expect(PartnershipStatus::TERMINATED->isTerminal())->toBeTrue();
        });

        it('returns false for active', function () {
            expect(PartnershipStatus::ACTIVE->isTerminal())->toBeFalse();
        });
    });
});
