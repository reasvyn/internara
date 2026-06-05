<?php

declare(strict_types=1);

namespace Tests\Unit\Setup\Entities;

use App\Setup\Entities\SetupState;
use Carbon\Carbon;

test('hasStoredToken returns true when setup token is set', function () {
    $state = new SetupState(
        dbInstalled: false,
        setupToken: 'encrypted-token',
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
    );

    expect($state->hasStoredToken())->toBeTrue();
});

test('hasStoredToken returns false when setup token is null', function () {
    $state = new SetupState(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
    );

    expect($state->hasStoredToken())->toBeFalse();
});

test('isTokenExpired returns true when tokenExpiresAt is null', function () {
    $state = new SetupState(
        dbInstalled: false,
        setupToken: 'token',
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
    );

    expect($state->isTokenExpired())->toBeTrue();
});

test('isTokenExpired returns true when token is past expiration', function () {
    $state = new SetupState(
        dbInstalled: false,
        setupToken: 'token',
        tokenExpiresAt: Carbon::now()->subMinutes(5),
        completedSteps: [],
        recoveryKey: null,
    );

    expect($state->isTokenExpired())->toBeTrue();
});

test('isTokenExpired returns false when token is not yet expired', function () {
    $state = new SetupState(
        dbInstalled: false,
        setupToken: 'token',
        tokenExpiresAt: Carbon::now()->addMinutes(30),
        completedSteps: [],
        recoveryKey: null,
    );

    expect($state->isTokenExpired())->toBeFalse();
});

test('isTokenExpired accepts custom now timestamp', function () {
    $expiresAt = Carbon::parse('2026-06-01 12:00:00');
    $now = Carbon::parse('2026-06-01 12:30:00');

    $state = new SetupState(
        dbInstalled: false,
        setupToken: 'token',
        tokenExpiresAt: $expiresAt,
        completedSteps: [],
        recoveryKey: null,
    );

    expect($state->isTokenExpired($now))->toBeTrue();
    expect($state->isTokenExpired(Carbon::parse('2026-06-01 11:00:00')))->toBeFalse();
});

test('validateToken returns false when token is expired', function () {
    $state = new SetupState(
        dbInstalled: false,
        setupToken: 'token',
        tokenExpiresAt: Carbon::now()->subMinutes(5),
        completedSteps: [],
        recoveryKey: null,
    );

    expect($state->validateToken('decrypted', 'input'))->toBeFalse();
});

test('validateToken returns true when decrypted token matches input', function () {
    $state = new SetupState(
        dbInstalled: false,
        setupToken: 'token',
        tokenExpiresAt: Carbon::now()->addMinutes(30),
        completedSteps: [],
        recoveryKey: null,
    );

    expect($state->validateToken('secret-token', 'secret-token'))->toBeTrue();
});

test('validateToken returns false when decrypted token does not match input', function () {
    $state = new SetupState(
        dbInstalled: false,
        setupToken: 'token',
        tokenExpiresAt: Carbon::now()->addMinutes(30),
        completedSteps: [],
        recoveryKey: null,
    );

    expect($state->validateToken('real-token', 'wrong-token'))->toBeFalse();
});

test('isStepCompleted returns true for completed steps', function () {
    $state = new SetupState(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: ['account', 'school', 'finalize'],
        recoveryKey: null,
    );

    expect($state->isStepCompleted('account'))->toBeTrue();
    expect($state->isStepCompleted('school'))->toBeTrue();
    expect($state->isStepCompleted('finalize'))->toBeTrue();
});

test('isStepCompleted returns false for incomplete steps', function () {
    $state = new SetupState(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: ['account'],
        recoveryKey: null,
    );

    expect($state->isStepCompleted('school'))->toBeFalse();
    expect($state->isStepCompleted('internship'))->toBeFalse();
});

test('allStepsCompleted returns true when all expected wizard steps are completed', function () {
    config()->set('setup.wizard.step_keys', ['welcome', 'account', 'school', 'department', 'internship', 'finalize', 'complete']);

    $state = new SetupState(
        dbInstalled: true,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: ['welcome', 'account', 'school', 'department', 'internship', 'finalize', 'complete'],
        recoveryKey: null,
    );

    expect($state->allStepsCompleted())->toBeTrue();
});

test('allStepsCompleted returns false when some wizard steps are missing', function () {
    config()->set('setup.wizard.step_keys', ['welcome', 'account', 'school', 'department', 'internship', 'finalize', 'complete']);

    $state = new SetupState(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: ['welcome', 'account', 'school'],
        recoveryKey: null,
    );

    expect($state->allStepsCompleted())->toBeFalse();
});

test('allStepsCompleted returns true when no config but steps exist', function () {
    config()->set('setup.wizard.step_keys', []);

    $state = new SetupState(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: ['welcome', 'account'],
        recoveryKey: null,
    );

    expect($state->allStepsCompleted())->toBeTrue();
});

test('isWithinFinalizationWindow returns false when updatedAt is null', function () {
    $state = new SetupState(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
    );

    expect($state->isWithinFinalizationWindow())->toBeFalse();
});

test('isWithinFinalizationWindow returns true when within configured window', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-05 12:00:00'));

    $state = new SetupState(
        dbInstalled: true,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
        updatedAt: Carbon::parse('2026-06-05 11:58:00'),
    );

    expect($state->isWithinFinalizationWindow(5))->toBeTrue();

    Carbon::setTestNow();
});

test('isWithinFinalizationWindow returns false when outside configured window', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-05 12:10:00'));

    $state = new SetupState(
        dbInstalled: true,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
        updatedAt: Carbon::parse('2026-06-05 12:00:00'),
    );

    expect($state->isWithinFinalizationWindow(5))->toBeFalse();

    Carbon::setTestNow();
});

test('isWithinFinalizationWindowSeconds returns false when updatedAt is null', function () {
    $state = new SetupState(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
    );

    expect($state->isWithinFinalizationWindowSeconds())->toBeFalse();
});

test('isWithinFinalizationWindowSeconds returns true when within configured seconds', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-05 12:00:15'));

    $state = new SetupState(
        dbInstalled: true,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
        updatedAt: Carbon::parse('2026-06-05 12:00:00'),
    );

    expect($state->isWithinFinalizationWindowSeconds(30))->toBeTrue();

    Carbon::setTestNow();
});

test('isWithinFinalizationWindowSeconds returns false when outside configured seconds', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-05 12:01:00'));

    $state = new SetupState(
        dbInstalled: true,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
        updatedAt: Carbon::parse('2026-06-05 12:00:00'),
    );

    expect($state->isWithinFinalizationWindowSeconds(30))->toBeFalse();

    Carbon::setTestNow();
});

test('hasRecoveryKey returns true when recovery key is set', function () {
    $state = new SetupState(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: 'hashed-key',
    );

    expect($state->hasRecoveryKey())->toBeTrue();
});

test('hasRecoveryKey returns false when recovery key is null', function () {
    $state = new SetupState(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
    );

    expect($state->hasRecoveryKey())->toBeFalse();
});

test('tokenVersion returns the stored version', function () {
    $state = new SetupState(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
        updatedAt: null,
        tokenVersion: 3,
    );

    expect($state->tokenVersion())->toBe(3);
});

test('tokenVersion defaults to zero', function () {
    $state = new SetupState(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
    );

    expect($state->tokenVersion())->toBe(0);
});

test('isInstalled returns true when dbInstalled is true', function () {
    $state = new SetupState(
        dbInstalled: true,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
    );

    expect($state->isInstalled())->toBeTrue();
});

test('isInstalled returns false when dbInstalled is false', function () {
    $state = new SetupState(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
    );

    expect($state->isInstalled())->toBeFalse();
});

test('updatedAt returns the stored carbon instance', function () {
    $now = Carbon::parse('2026-06-05 12:30:00');

    $state = new SetupState(
        dbInstalled: true,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
        updatedAt: $now,
    );

    expect($state->updatedAt())->toBe($now);
});

test('updatedAt returns null when not set', function () {
    $state = new SetupState(
        dbInstalled: false,
        setupToken: null,
        tokenExpiresAt: null,
        completedSteps: [],
        recoveryKey: null,
    );

    expect($state->updatedAt())->toBeNull();
});
