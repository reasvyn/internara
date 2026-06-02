<?php

declare(strict_types=1);

use App\Domain\Setup\Entities\SetupState;
use Carbon\Carbon;

describe('SetupState', function () {
    it('is final readonly entity', function () {
        $ref = new ReflectionClass(SetupState::class);

        expect($ref->isReadOnly())->toBeTrue();
    });

    it('detects installed state', function () {
        $state = new SetupState(
            dbInstalled: true,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: [],
            recoveryKey: null,
        );

        expect($state->isInstalled())->toBeTrue();
    });

    it('detects not installed state', function () {
        $state = new SetupState(
            dbInstalled: false,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: [],
            recoveryKey: null,
        );

        expect($state->isInstalled())->toBeFalse();
    });

    it('detects stored token', function () {
        $state = new SetupState(
            dbInstalled: false,
            setupToken: 'encrypted-token',
            tokenExpiresAt: null,
            completedSteps: [],
            recoveryKey: null,
        );

        expect($state->hasStoredToken())->toBeTrue();
    });

    it('detects missing token', function () {
        $state = new SetupState(
            dbInstalled: false,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: [],
            recoveryKey: null,
        );

        expect($state->hasStoredToken())->toBeFalse();
    });

    it('returns not expired when token expiry is in the future', function () {
        $state = new SetupState(
            dbInstalled: false,
            setupToken: 'token',
            tokenExpiresAt: Carbon::now()->addHour(),
            completedSteps: [],
            recoveryKey: null,
        );

        expect($state->isTokenExpired())->toBeFalse();
    });

    it('returns expired when token expiry is in the past', function () {
        $state = new SetupState(
            dbInstalled: false,
            setupToken: 'token',
            tokenExpiresAt: Carbon::now()->subMinute(),
            completedSteps: [],
            recoveryKey: null,
        );

        expect($state->isTokenExpired())->toBeTrue();
    });

    it('returns expired when token expiry is null', function () {
        $state = new SetupState(
            dbInstalled: false,
            setupToken: 'token',
            tokenExpiresAt: null,
            completedSteps: [],
            recoveryKey: null,
        );

        expect($state->isTokenExpired())->toBeTrue();
    });

    it('validates token with hash_equals comparison', function () {
        $state = new SetupState(
            dbInstalled: false,
            setupToken: 'encrypted',
            tokenExpiresAt: Carbon::now()->addHour(),
            completedSteps: [],
            recoveryKey: null,
        );

        expect($state->validateToken('stored-plain', 'stored-plain'))->toBeTrue();
    });

    it('rejects token when hashes do not match', function () {
        $state = new SetupState(
            dbInstalled: false,
            setupToken: 'encrypted',
            tokenExpiresAt: Carbon::now()->addHour(),
            completedSteps: [],
            recoveryKey: null,
        );

        expect($state->validateToken('stored-plain', 'wrong-input'))->toBeFalse();
    });

    it('rejects token when expired even if hashes match', function () {
        $state = new SetupState(
            dbInstalled: false,
            setupToken: 'encrypted',
            tokenExpiresAt: Carbon::now()->subHour(),
            completedSteps: [],
            recoveryKey: null,
        );

        expect($state->validateToken('stored-plain', 'stored-plain'))->toBeFalse();
    });

    it('checks if a specific step is completed', function () {
        $state = new SetupState(
            dbInstalled: false,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: ['school', 'department'],
            recoveryKey: null,
        );

        expect($state->isStepCompleted('school'))->toBeTrue()
            ->and($state->isStepCompleted('account'))->toBeFalse();
    });

    it('checks all steps completed against config', function () {
        config(['setup.wizard.step_keys' => ['a', 'b', 'c']]);
        $state = new SetupState(
            dbInstalled: false,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: ['a', 'b', 'c'],
            recoveryKey: null,
        );

        expect($state->allStepsCompleted())->toBeTrue();
    });

    it('returns false for all steps completed when some are missing', function () {
        config(['setup.wizard.step_keys' => ['a', 'b', 'c']]);
        $state = new SetupState(
            dbInstalled: false,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: ['a', 'b'],
            recoveryKey: null,
        );

        expect($state->allStepsCompleted())->toBeFalse();
    });

    it('returns true for all steps completed when config steps is empty but steps exist', function () {
        config(['setup.wizard.step_keys' => []]);
        $state = new SetupState(
            dbInstalled: false,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: ['school'],
            recoveryKey: null,
        );

        expect($state->allStepsCompleted())->toBeTrue();
    });

    it('checks within finalization window (minutes)', function () {
        $state = new SetupState(
            dbInstalled: false,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: [],
            recoveryKey: null,
            updatedAt: Carbon::now()->subMinutes(2),
        );

        expect($state->isWithinFinalizationWindow(5))->toBeTrue()
            ->and($state->isWithinFinalizationWindow(1))->toBeFalse();
    });

    it('returns false for finalization window when updatedAt is null', function () {
        $state = new SetupState(
            dbInstalled: false,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: [],
            recoveryKey: null,
        );

        expect($state->isWithinFinalizationWindow())->toBeFalse();
    });

    it('checks within finalization window (seconds)', function () {
        $state = new SetupState(
            dbInstalled: false,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: [],
            recoveryKey: null,
            updatedAt: Carbon::now()->subSeconds(10),
        );

        expect($state->isWithinFinalizationWindowSeconds(30))->toBeTrue()
            ->and($state->isWithinFinalizationWindowSeconds(5))->toBeFalse();
    });

    it('returns false for seconds window when updatedAt is null', function () {
        $state = new SetupState(
            dbInstalled: false,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: [],
            recoveryKey: null,
        );

        expect($state->isWithinFinalizationWindowSeconds())->toBeFalse();
    });

    it('detects recovery key presence', function () {
        $state = new SetupState(
            dbInstalled: false,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: [],
            recoveryKey: 'hashed-key',
        );

        expect($state->hasRecoveryKey())->toBeTrue();
    });

    it('detects missing recovery key', function () {
        $state = new SetupState(
            dbInstalled: false,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: [],
            recoveryKey: null,
        );

        expect($state->hasRecoveryKey())->toBeFalse();
    });
});
