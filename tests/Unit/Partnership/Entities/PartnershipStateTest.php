<?php

declare(strict_types=1);

use App\Domain\Core\Entities\BaseEntity;
use App\Domain\Partnership\Entities\PartnershipState;
use App\Domain\Partnership\Enums\PartnershipStatus;

describe('PartnershipState entity', function () {
    it('detects active status', function () {
        $entity = new PartnershipState(status: PartnershipStatus::ACTIVE, endDate: null);

        expect($entity->isActive())->toBeTrue()
            ->and($entity->isExpired())->toBeFalse()
            ->and($entity->isTerminated())->toBeFalse();
    });

    it('detects expired status', function () {
        $entity = new PartnershipState(status: PartnershipStatus::EXPIRED, endDate: null);

        expect($entity->isExpired())->toBeTrue()
            ->and($entity->isActive())->toBeFalse();
    });

    it('detects terminated status', function () {
        $entity = new PartnershipState(status: PartnershipStatus::TERMINATED, endDate: null);

        expect($entity->isTerminated())->toBeTrue()
            ->and($entity->isActive())->toBeFalse();
    });

    it('detects expiring soon', function () {
        $entity = new PartnershipState(
            status: PartnershipStatus::ACTIVE,
            endDate: now()->addDays(15)->format('Y-m-d'),
        );

        expect($entity->isExpiringSoon(30))->toBeTrue();
    });

    it('does not detect expiring soon for non-active', function () {
        $entity = new PartnershipState(
            status: PartnershipStatus::EXPIRED,
            endDate: now()->addDays(15)->format('Y-m-d'),
        );

        expect($entity->isExpiringSoon(30))->toBeFalse();
    });

    it('does not detect expiring soon beyond threshold', function () {
        $entity = new PartnershipState(
            status: PartnershipStatus::ACTIVE,
            endDate: now()->addDays(60)->format('Y-m-d'),
        );

        expect($entity->isExpiringSoon(30))->toBeFalse();
    });

    it('can be deleted when expired', function () {
        $entity = new PartnershipState(status: PartnershipStatus::EXPIRED, endDate: null);

        expect($entity->canBeDeleted())->toBeTrue();
    });

    it('cannot be deleted when active', function () {
        $entity = new PartnershipState(status: PartnershipStatus::ACTIVE, endDate: null);

        expect($entity->canBeDeleted())->toBeFalse();
    });

    it('is final readonly', function () {
        $ref = new ReflectionClass(PartnershipState::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    it('extends BaseEntity', function () {
        expect(PartnershipState::class)->toExtend(BaseEntity::class);
    });
});
