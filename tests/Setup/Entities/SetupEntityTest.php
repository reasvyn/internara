<?php

declare(strict_types=1);

use App\Setup\Entities\SetupEntity;
use Carbon\Carbon;

test('setup entity returns constructor values via getters', function () {
    $now = Carbon::now();
    $entity = new SetupEntity(
        dbInstalled: true,
        setupToken: 'token-abc',
        tokenExpiresAt: $now,
        completedSteps: ['admin', 'school'],
        recoveryKey: 'recovery-key',
        updatedAt: $now,
        tokenVersion: 2,
    );

    expect($entity->isInstalled())->toBeTrue();
    expect($entity->setupToken())->toBe('token-abc');
    expect($entity->tokenExpiresAt())->toBe($now);
    expect($entity->recoveryKey())->toBe('recovery-key');
    expect($entity->updatedAt())->toBe($now);
    expect($entity->tokenVersion())->toBe(2);
});

test('setup entity detects stored token', function () {
    $hasToken = new SetupEntity(
        dbInstalled: false,
        setupToken: 'some-token',
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
    );
    expect($hasToken->hasStoredToken())->toBeTrue();

    $noToken = new SetupEntity(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
    );
    expect($noToken->hasStoredToken())->toBeFalse();
});

test('setup entity detects expired token', function () {
    $expired = new SetupEntity(
        dbInstalled: false,
        setupToken: 'token',
        tokenExpiresAt: Carbon::now()->subHour(),
        completedSteps: [],
        recoveryKey: null,
    );
    expect($expired->isTokenExpired())->toBeTrue();

    $valid = new SetupEntity(
        dbInstalled: false,
        setupToken: 'token',
        tokenExpiresAt: Carbon::now()->addHour(),
        completedSteps: [],
        recoveryKey: null,
    );
    expect($valid->isTokenExpired())->toBeFalse();

    $noExpiry = new SetupEntity(
        dbInstalled: false,
        setupToken: 'token',
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
    );
    expect($noExpiry->isTokenExpired())->toBeTrue();
});

test('setup entity validates token', function () {
    $entity = new SetupEntity(
        dbInstalled: false,
        setupToken: 'encrypted-token',
        tokenExpiresAt: Carbon::now()->addHour(),
        completedSteps: [],
        recoveryKey: null,
    );

    expect($entity->validateToken('correct-token', 'correct-token'))->toBeTrue();
    expect($entity->validateToken('correct-token', 'wrong-token'))->toBeFalse();
});

test('setup entity validates token returns false when token is expired', function () {
    $entity = new SetupEntity(
        dbInstalled: false,
        setupToken: 'encrypted-token',
        tokenExpiresAt: Carbon::now()->subHour(),
        completedSteps: [],
        recoveryKey: null,
    );

    expect($entity->validateToken('any-token', 'any-token'))->toBeFalse();
});

test('setup entity tracks completed steps', function () {
    $entity = new SetupEntity(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: ['admin', 'school', 'department'],
        recoveryKey: null,
    );

    expect($entity->completedSteps())->toBe(['admin', 'school', 'department']);
    expect($entity->isStepCompleted('admin'))->toBeTrue();
    expect($entity->isStepCompleted('database'))->toBeFalse();
});

test('setup entity checks all steps completed', function () {
    config()->set('setup.wizard.step_keys', ['admin', 'school']);

    $complete = new SetupEntity(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: ['admin', 'school'],
        recoveryKey: null,
    );
    expect($complete->allStepsCompleted())->toBeTrue();

    $incomplete = new SetupEntity(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: ['admin'],
        recoveryKey: null,
    );
    expect($incomplete->allStepsCompleted())->toBeFalse();
});

test('setup entity all steps completed with empty config returns true when any steps completed', function () {
    config()->set('setup.wizard.step_keys', []);

    $withSteps = new SetupEntity(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: ['admin'],
        recoveryKey: null,
    );
    expect($withSteps->allStepsCompleted())->toBeTrue();

    $noSteps = new SetupEntity(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
    );
    expect($noSteps->allStepsCompleted())->toBeFalse();
});

test('setup entity checks finalization window', function () {
    $recent = new SetupEntity(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: ['admin'],
        recoveryKey: null,
        updatedAt: Carbon::now()->subMinutes(2),
    );
    expect($recent->isWithinFinalizationWindow())->toBeTrue();
    expect($recent->isWithinFinalizationWindow(10))->toBeTrue();

    $old = new SetupEntity(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: ['admin'],
        recoveryKey: null,
        updatedAt: Carbon::now()->subMinutes(10),
    );
    expect($old->isWithinFinalizationWindow())->toBeFalse();

    $noUpdate = new SetupEntity(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
    );
    expect($noUpdate->isWithinFinalizationWindow())->toBeFalse();
});

test('setup entity checks finalization window in seconds', function () {
    $recent = new SetupEntity(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
        updatedAt: Carbon::now()->subSeconds(15),
    );
    expect($recent->isWithinFinalizationWindowSeconds())->toBeTrue();
    expect($recent->isWithinFinalizationWindowSeconds(60))->toBeTrue();

    $old = new SetupEntity(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
        updatedAt: Carbon::now()->subMinutes(2),
    );
    expect($old->isWithinFinalizationWindowSeconds())->toBeFalse();

    $noUpdate = new SetupEntity(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
    );
    expect($noUpdate->isWithinFinalizationWindowSeconds())->toBeFalse();
});

test('setup entity detects recovery key', function () {
    $has = new SetupEntity(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: 'some-key',
    );
    expect($has->hasRecoveryKey())->toBeTrue();

    $none = new SetupEntity(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
    );
    expect($none->hasRecoveryKey())->toBeFalse();
});
