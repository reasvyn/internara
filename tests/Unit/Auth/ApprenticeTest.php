<?php

declare(strict_types=1);

use App\Domain\Auth\Entities\Apprentice;
use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Core\Entities\BaseEntity;

describe('Apprentice entity', function () {
    it('detects suspended status', function () {
        $entity = new Apprentice(
            status: AccountStatus::SUSPENDED,
            isLocked: false,
            setupRequired: false,
        );

        expect($entity->isSuspended())->toBeTrue()
            ->and($entity->status()->allowsLogin())->toBeFalse();
    });

    it('detects archived status', function () {
        $entity = new Apprentice(
            status: AccountStatus::ARCHIVED,
            isLocked: false,
            setupRequired: false,
        );

        expect($entity->isArchived())->toBeTrue();
    });

    it('detects inactive status', function () {
        $entity = new Apprentice(
            status: AccountStatus::INACTIVE,
            isLocked: false,
            setupRequired: false,
        );

        expect($entity->isInactive())->toBeTrue();
    });

    it('detects locked state', function () {
        $entity = new Apprentice(
            status: AccountStatus::VERIFIED,
            isLocked: true,
            setupRequired: false,
        );

        expect($entity->isLocked())->toBeTrue();
    });

    it('detects setup requirement', function () {
        $entity = new Apprentice(
            status: AccountStatus::VERIFIED,
            isLocked: false,
            setupRequired: true,
        );

        expect($entity->requiresSetup())->toBeTrue();
    });

    it('validates status transitions', function () {
        $entity = new Apprentice(
            status: AccountStatus::VERIFIED,
            isLocked: false,
            setupRequired: false,
        );

        expect($entity->canTransitionTo(AccountStatus::SUSPENDED))->toBeTrue()
            ->and($entity->canTransitionTo(AccountStatus::PROVISIONED))->toBeFalse();
    });

    it('is final readonly', function () {
        $ref = new ReflectionClass(Apprentice::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    it('extends BaseEntity', function () {
        expect(Apprentice::class)->toExtend(BaseEntity::class);
    });
});
