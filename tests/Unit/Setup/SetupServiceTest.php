<?php

declare(strict_types=1);

namespace Tests\Unit\Setup;

use App\Domain\Setup\Services\SetupService;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $service = new SetupService;
    if ($service->isInstalled()) {
        File::delete(storage_path('app/.installed'));
    }
    $service->clearSession();
});

afterEach(function () {
    $service = new SetupService;
    if ($service->isInstalled()) {
        File::delete(storage_path('app/.installed'));
    }
    $service->clearSession();
});

// ─── SetupService Tests ───

test('isInstalled returns false when lock file does not exist', function () {
    $service = new SetupService;

    expect($service->isInstalled())->toBeFalse();
});

test('isInstalled returns true when lock file exists', function () {
    File::put(
        storage_path('app/.installed'),
        json_encode(['installed_at' => now()->toIso8601String()]),
    );
    $service = new SetupService;

    expect($service->isInstalled())->toBeTrue();
});

test('generateToken creates encrypted session token', function () {
    $service = new SetupService;
    $token = $service->generateToken();

    expect($token)->toBeString()->toHaveLength(64)->and($service->getToken())->toBe($token);
});

test('validateToken returns true for valid token', function () {
    $service = new SetupService;
    $token = $service->generateToken();

    expect($service->validateToken($token))->toBeTrue();
});

test('validateToken returns false for invalid token', function () {
    $service = new SetupService;
    $service->generateToken();

    expect($service->validateToken('invalid-token'))->toBeFalse();
});

test('validateToken returns false when no token exists', function () {
    $service = new SetupService;

    expect($service->validateToken('any-token'))->toBeFalse();
});

test('completeStep adds step to completed list', function () {
    $service = new SetupService;
    $service->completeStep('welcome');

    expect($service->isStepCompleted('welcome'))->toBeTrue();
});

test('completeStep does not duplicate steps', function () {
    $service = new SetupService;
    $service->completeStep('welcome');
    $service->completeStep('welcome');

    expect($service->getCompletedSteps())->toHaveCount(1);
});

test('getProgress calculates correctly', function () {
    $service = new SetupService;
    $service->completeStep('welcome');

    // 1 out of 6 steps = 16.66% -> 17%
    expect($service->getProgress())->toBe(17);
});

test('finalize creates lock file and clears session', function () {
    $service = new SetupService;
    $service->generateToken();
    $service->completeStep('welcome');
    $service->finalize();

    expect($service->isInstalled())
        ->toBeTrue()
        ->and($service->getToken())
        ->toBeNull()
        ->and($service->getCompletedSteps())
        ->toBeEmpty();
});

test('reset removes lock file and generates new token', function () {
    $service = new SetupService;
    $service->finalize();
    $token = $service->reset();

    expect($service->isInstalled())->toBeFalse()->and($token)->toBeString()->toHaveLength(64);
});

test('authorizeSession sets authorized flag for specific token', function () {
    $service = new SetupService;
    $token = $service->generateToken();

    expect($service->isSessionAuthorized())->toBeFalse();

    $service->authorizeSession($token);

    expect($service->isSessionAuthorized())->toBeTrue();
});

test('isSessionAuthorized returns false if token changes', function () {
    $service = new SetupService;
    $token1 = $service->generateToken();
    $service->authorizeSession($token1);

    expect($service->isSessionAuthorized())->toBeTrue();

    // Reset and generate new token (this simulates setup:install)
    $token2 = $service->reset();

    expect($service->isSessionAuthorized())->toBeFalse();
});

test('storeEntityId persists and retrieves entity IDs', function () {
    $service = new SetupService;
    $service->storeEntityId('school_id', 'abc-123');

    expect($service->getEntityId('school_id'))->toBe('abc-123');
});

test('getLockData returns null when not installed', function () {
    $service = new SetupService;

    expect($service->getLockData())->toBeNull();
});

test('getLockData returns lock file content when installed', function () {
    $service = new SetupService;
    $service->finalize();

    $data = $service->getLockData();

    expect($data)->toHaveKey('installed_at')->toHaveKey('version');
});

test('isTokenExpired returns true when no token exists', function () {
    $service = new SetupService;

    expect($service->isTokenExpired())->toBeTrue();
});

test('setCurrentStep clamps value between 1 and 7', function () {
    $service = new SetupService;
    $service->setCurrentStep(0);

    expect($service->getCurrentStep())->toBe(1);

    $service->setCurrentStep(99);

    expect($service->getCurrentStep())->toBe(7);
});
