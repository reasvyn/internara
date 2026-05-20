<?php

declare(strict_types=1);

use App\Domain\Core\Entities\BaseEntity;
use App\Domain\Registration\Entities\RegistrationState;
use Carbon\Carbon;

describe('RegistrationState entity', function () {
    it('detects active status', function () {
        $entity = new RegistrationState(
            status: 'active',
            startDate: null,
            endDate: null,
            hasPlacement: true,
        );

        expect($entity->isActive())->toBeTrue()
            ->and($entity->isPending())->toBeFalse();
    });

    it('detects pending status', function () {
        $entity = new RegistrationState(
            status: 'pending',
            startDate: null,
            endDate: null,
            hasPlacement: false,
        );

        expect($entity->isPending())->toBeTrue()
            ->and($entity->isActive())->toBeFalse();
    });

    it('can be approved when pending with placement', function () {
        $entity = new RegistrationState(
            status: 'pending',
            startDate: null,
            endDate: null,
            hasPlacement: true,
        );

        expect($entity->canBeApproved())->toBeTrue();
    });

    it('cannot be approved when pending without placement', function () {
        $entity = new RegistrationState(
            status: 'pending',
            startDate: null,
            endDate: null,
            hasPlacement: false,
        );

        expect($entity->canBeApproved())->toBeFalse();
    });

    it('cannot be approved when not pending', function () {
        $entity = new RegistrationState(
            status: 'active',
            startDate: null,
            endDate: null,
            hasPlacement: true,
        );

        expect($entity->canBeApproved())->toBeFalse();
    });

    it('detects ongoing period', function () {
        $entity = new RegistrationState(
            status: 'active',
            startDate: Carbon::now()->subDays(10),
            endDate: Carbon::now()->addDays(10),
            hasPlacement: true,
        );

        expect($entity->isCurrentlyOngoing())->toBeTrue();
    });

    it('detects ended period', function () {
        $entity = new RegistrationState(
            status: 'active',
            startDate: Carbon::now()->subDays(20),
            endDate: Carbon::now()->subDays(10),
            hasPlacement: true,
        );

        expect($entity->hasEnded())->toBeTrue();
    });

    it('calculates days remaining', function () {
        $now = Carbon::now();
        $entity = new RegistrationState(
            status: 'active',
            startDate: $now->copy()->subDays(10),
            endDate: $now->copy()->addDays(5),
            hasPlacement: true,
        );

        expect($entity->daysRemaining($now))->toBe(5);
    });

    it('calculates total duration', function () {
        $entity = new RegistrationState(
            status: 'active',
            startDate: Carbon::now()->subDays(30),
            endDate: Carbon::now()->addDays(30),
            hasPlacement: true,
        );

        expect($entity->totalDuration())->toBe(60);
    });

    it('is final readonly', function () {
        $ref = new ReflectionClass(RegistrationState::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    it('extends BaseEntity', function () {
        expect(RegistrationState::class)->toExtend(BaseEntity::class);
    });
});
