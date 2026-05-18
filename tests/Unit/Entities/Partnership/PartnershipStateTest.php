<?php

declare(strict_types=1);

use App\Entities\Partnership\PartnershipState;
use App\Enums\Shared\PartnershipStatus;

describe('PartnershipState', function () {
    describe('isActive', function () {
        it('returns true when active', function () {
            $state = new PartnershipState(PartnershipStatus::ACTIVE, '2026-12-31');

            expect($state->isActive())->toBeTrue();
        });

        it('returns false when expired', function () {
            $state = new PartnershipState(PartnershipStatus::EXPIRED, '2026-01-01');

            expect($state->isActive())->toBeFalse();
        });
    });

    describe('isExpiringSoon', function () {
        it('returns true when within 30 days of expiry', function () {
            $nearFuture = now()->addDays(15)->format('Y-m-d');
            $state = new PartnershipState(PartnershipStatus::ACTIVE, $nearFuture);

            expect($state->isExpiringSoon())->toBeTrue();
        });

        it('returns false when far from expiry', function () {
            $farFuture = now()->addMonths(6)->format('Y-m-d');
            $state = new PartnershipState(PartnershipStatus::ACTIVE, $farFuture);

            expect($state->isExpiringSoon())->toBeFalse();
        });

        it('returns false when not active', function () {
            $state = new PartnershipState(PartnershipStatus::EXPIRED, now()->format('Y-m-d'));

            expect($state->isExpiringSoon())->toBeFalse();
        });

        it('returns false when null end date', function () {
            $state = new PartnershipState(PartnershipStatus::ACTIVE, null);

            expect($state->isExpiringSoon())->toBeFalse();
        });
    });

    describe('canBeDeleted', function () {
        it('returns true for expired', function () {
            $state = new PartnershipState(PartnershipStatus::EXPIRED, null);

            expect($state->canBeDeleted())->toBeTrue();
        });

        it('returns true for terminated', function () {
            $state = new PartnershipState(PartnershipStatus::TERMINATED, null);

            expect($state->canBeDeleted())->toBeTrue();
        });

        it('returns false for active', function () {
            $state = new PartnershipState(PartnershipStatus::ACTIVE, '2026-12-31');

            expect($state->canBeDeleted())->toBeFalse();
        });
    });
});
