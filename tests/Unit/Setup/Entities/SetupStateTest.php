<?php

declare(strict_types=1);

use App\Domain\Core\Entities\BaseEntity;
use App\Domain\Setup\Entities\SetupState;
use Carbon\Carbon;

describe('SetupState entity', function () {
    it('detects installed state', function () {
        $entity = new SetupState(
            dbInstalled: true,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: [],
            recoveryKey: null,
        );

        expect($entity->isInstalled())->toBeTrue();
    });

    it('detects not installed state', function () {
        $entity = new SetupState(
            dbInstalled: false,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: [],
            recoveryKey: null,
        );

        expect($entity->isInstalled())->toBeFalse();
    });

    it('detects stored token', function () {
        $entity = new SetupState(
            dbInstalled: false,
            setupToken: 'some-token',
            tokenExpiresAt: now()->addHour(),
            completedSteps: [],
            recoveryKey: null,
        );

        expect($entity->hasStoredToken())->toBeTrue();
    });

    it('detects missing token', function () {
        $entity = new SetupState(
            dbInstalled: false,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: [],
            recoveryKey: null,
        );

        expect($entity->hasStoredToken())->toBeFalse();
    });

    it('detects expired token', function () {
        $entity = new SetupState(
            dbInstalled: false,
            setupToken: 'token',
            tokenExpiresAt: Carbon::now()->subMinute(),
            completedSteps: [],
            recoveryKey: null,
        );

        expect($entity->isTokenExpired())->toBeTrue();
    });

    it('detects valid (non-expired) token', function () {
        $entity = new SetupState(
            dbInstalled: false,
            setupToken: 'token',
            tokenExpiresAt: Carbon::now()->addHour(),
            completedSteps: [],
            recoveryKey: null,
        );

        expect($entity->isTokenExpired())->toBeFalse();
    });

    it('treats null expiry as expired', function () {
        $entity = new SetupState(
            dbInstalled: false,
            setupToken: 'token',
            tokenExpiresAt: null,
            completedSteps: [],
            recoveryKey: null,
        );

        expect($entity->isTokenExpired())->toBeTrue();
    });

    it('validates token with hash_equals', function () {
        $entity = new SetupState(
            dbInstalled: false,
            setupToken: 'encrypted-token',
            tokenExpiresAt: Carbon::now()->addHour(),
            completedSteps: [],
            recoveryKey: null,
        );

        expect($entity->validateToken('stored-decrypted', 'stored-decrypted'))->toBeTrue()
            ->and($entity->validateToken('stored-decrypted', 'wrong-token'))->toBeFalse();
    });

    it('rejects token validation when expired', function () {
        $entity = new SetupState(
            dbInstalled: false,
            setupToken: 'encrypted-token',
            tokenExpiresAt: Carbon::now()->subHour(),
            completedSteps: [],
            recoveryKey: null,
        );

        expect($entity->validateToken('stored-decrypted', 'stored-decrypted', Carbon::now()))->toBeFalse();
    });

    it('checks single step completion', function () {
        $entity = new SetupState(
            dbInstalled: false,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: ['school', 'account'],
            recoveryKey: null,
        );

        expect($entity->isStepCompleted('school'))->toBeTrue()
            ->and($entity->isStepCompleted('department'))->toBeFalse();
    });

    it('checks all steps completed', function () {
        $entity = new SetupState(
            dbInstalled: true,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: ['welcome', 'school', 'department', 'account', 'internship', 'finalize', 'complete'],
            recoveryKey: null,
        );

        expect($entity->allStepsCompleted())->toBeTrue();
    });

    it('detects incomplete steps', function () {
        $entity = new SetupState(
            dbInstalled: false,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: ['school'],
            recoveryKey: null,
        );

        expect($entity->allStepsCompleted())->toBeFalse();
    });

    it('checks finalization window', function () {
        $entity = new SetupState(
            dbInstalled: true,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: ['school', 'department', 'account'],
            recoveryKey: null,
            updatedAt: Carbon::now()->subMinutes(2),
        );

        expect($entity->isWithinFinalizationWindow(5))->toBeTrue()
            ->and($entity->isWithinFinalizationWindow(1))->toBeFalse();
    });

    it('detects recovery key presence', function () {
        $entity = new SetupState(
            dbInstalled: true,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: [],
            recoveryKey: 'some-key',
        );

        expect($entity->hasRecoveryKey())->toBeTrue();
    });

    it('returns updatedAt', function () {
        $now = Carbon::now();
        $entity = new SetupState(
            dbInstalled: false,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: [],
            recoveryKey: null,
            updatedAt: $now,
        );

        expect($entity->updatedAt())->toBe($now);
    });

    it('is final readonly', function () {
        $ref = new ReflectionClass(SetupState::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    it('extends BaseEntity', function () {
        expect(SetupState::class)->toExtend(BaseEntity::class);
    });
});
