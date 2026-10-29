<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\Registration\Entities\RegistrationState;
use App\Modules\Enrollment\Domain\Registration\Enums\RegistrationStatus;

describe('1SPKL: RegistrationStatus lifecycle', function () {
    test('1SPKL-FR-SPKL-004: registration status cases define valid labels and helpers', function () {
        expect(RegistrationStatus::PREPARATION->value)->toBe('preparation')
            ->and(RegistrationStatus::PENDING->value)->toBe('pending')
            ->and(RegistrationStatus::CONFIRMED->value)->toBe('confirmed')
            ->and(RegistrationStatus::ACTIVE->value)->toBe('active')
            ->and(RegistrationStatus::COMPLETED->value)->toBe('completed')
            ->and(RegistrationStatus::REJECTED->value)->toBe('rejected');

        expect(RegistrationStatus::PREPARATION->isPreparation())->toBeTrue()
            ->and(RegistrationStatus::CONFIRMED->isConfirmed())->toBeTrue()
            ->and(RegistrationStatus::COMPLETED->isCompleted())->toBeTrue()
            ->and(RegistrationStatus::COMPLETED->isTerminal())->toBeTrue()
            ->and(RegistrationStatus::PREPARATION->isTerminal())->toBeFalse();
    });

    test('1SPKL-FR-SPKL-004: valid linear status transitions are enforced', function () {
        $prep = RegistrationStatus::PREPARATION;
        expect($prep->canTransitionTo(RegistrationStatus::PENDING))->toBeTrue()
            ->and($prep->canTransitionTo(RegistrationStatus::CONFIRMED))->toBeTrue()
            ->and($prep->canTransitionTo(RegistrationStatus::COMPLETED))->toBeFalse();

        $pending = RegistrationStatus::PENDING;
        expect($pending->canTransitionTo(RegistrationStatus::CONFIRMED))->toBeTrue()
            ->and($pending->canTransitionTo(RegistrationStatus::REJECTED))->toBeTrue()
            ->and($pending->canTransitionTo(RegistrationStatus::ACTIVE))->toBeFalse();

        $confirmed = RegistrationStatus::CONFIRMED;
        expect($confirmed->canTransitionTo(RegistrationStatus::ACTIVE))->toBeTrue()
            ->and($confirmed->canTransitionTo(RegistrationStatus::REJECTED))->toBeTrue()
            ->and($confirmed->canTransitionTo(RegistrationStatus::COMPLETED))->toBeFalse();

        $active = RegistrationStatus::ACTIVE;
        expect($active->canTransitionTo(RegistrationStatus::COMPLETED))->toBeTrue()
            ->and($active->canTransitionTo(RegistrationStatus::PREPARATION))->toBeFalse();

        $completed = RegistrationStatus::COMPLETED;
        expect($completed->validTransitions())->toBeEmpty()
            ->and($completed->canTransitionTo(RegistrationStatus::ACTIVE))->toBeFalse();
    });

    test('1SPKL-DD-SPKL-001: registration state answers preparation confirmed and completed checks', function () {
        $statePrep = new RegistrationState(
            status: RegistrationStatus::PREPARATION,
            startDate: null,
            endDate: null,
            hasPlacement: false
        );

        expect($statePrep->isPreparation())->toBeTrue()
            ->and($statePrep->isActive())->toBeFalse()
            ->and($statePrep->isCompleted())->toBeFalse()
            ->and($statePrep->canTransitionTo(RegistrationStatus::PENDING))->toBeTrue()
            ->and($statePrep->canTransitionTo(RegistrationStatus::COMPLETED))->toBeFalse();

        $stateConfirmed = new RegistrationState(
            status: RegistrationStatus::CONFIRMED,
            startDate: null,
            endDate: null,
            hasPlacement: true
        );

        expect($stateConfirmed->isConfirmed())->toBeTrue()
            ->and($stateConfirmed->canTransitionTo(RegistrationStatus::ACTIVE))->toBeTrue();

        $stateCompleted = new RegistrationState(
            status: RegistrationStatus::COMPLETED,
            startDate: null,
            endDate: null,
            hasPlacement: true
        );

        expect($stateCompleted->isCompleted())->toBeTrue()
            ->and($stateCompleted->canTransitionTo(RegistrationStatus::ACTIVE))->toBeFalse();
    });
});
