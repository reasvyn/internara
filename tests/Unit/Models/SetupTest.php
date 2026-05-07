<?php

declare(strict_types=1);

use App\Models\Setup;
use Database\Factories\SetupFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    File::delete(base_path('.installed'));
    Setup::query()->delete();
});

it('can be created with factory', function () {
    $setup = SetupFactory::new()->create();

    expect($setup)->toBeInstanceOf(Setup::class)
        ->and($setup->id)->toBeUuid()
        ->and($setup->is_installed)->toBeFalse();
});

it('has uuid as primary key', function () {
    $setup = SetupFactory::new()->create();

    expect($setup->id)->toBeUuid();
});

it('casts attributes correctly', function () {
    $setup = SetupFactory::new()->create([
        'is_installed' => true,
        'token_expires_at' => '2026-05-07 12:00:00',
        'completed_steps' => ['welcome', 'school'],
    ]);

    expect($setup->is_installed)->toBeTrue()
        ->and($setup->token_expires_at)->toBeInstanceOf(DateTime::class)
        ->and($setup->completed_steps)->toBe(['welcome', 'school']);
});

it('is not installed by default', function () {
    expect(Setup::isInstalled())->toBeFalse();
});

it('detects installed via database', function () {
    SetupFactory::new()->installed()->create();
    expect(Setup::isInstalled())->toBeTrue();
});

it('detects installed via file', function () {
    File::put(base_path('.installed'), now()->toDateTimeString());
    expect(Setup::isInstalled())->toBeTrue();
});

it('can mark as installed', function () {
    Setup::markInstalled();
    expect(Setup::isInstalled())->toBeTrue()
        ->and(File::exists(base_path('.installed')))->toBeTrue();
});

it('can generate token', function () {
    $result = Setup::generateToken();

    expect($result)->toHaveKeys(['encrypted', 'plaintext', 'expires_at'])
        ->and(strlen($result['plaintext']))->toBe(64)
        ->and($result['expires_at'])->toBeInstanceOf(DateTime::class);

    $setup = Setup::first();
    expect($setup->setup_token)->not->toBeNull()
        ->and($setup->token_expires_at)->not->toBeNull();
});

it('can validate correct token', function () {
    $result = Setup::generateToken();
    expect(Setup::validateToken($result['plaintext']))->toBeTrue();
});

it('rejects invalid token', function () {
    Setup::generateToken();
    expect(Setup::validateToken('invalid-token'))->toBeFalse();
});

it('rejects expired token', function () {
    $result = Setup::generateToken();
    $setup = Setup::first();
    $setup->update(['token_expires_at' => now()->subHour()]);

    expect(Setup::validateToken($result['plaintext']))->toBeFalse();
});

it('rejects validation when no token set', function () {
    expect(Setup::validateToken('some-token'))->toBeFalse();
});

it('can invalidate token', function () {
    Setup::generateToken();
    Setup::invalidateToken();

    $setup = Setup::first();
    expect($setup->setup_token)->toBeNull()
        ->and($setup->token_expires_at)->toBeNull();
});

it('can get current step', function () {
    expect(Setup::getCurrentStep())->toBe('welcome');

    $setup = SetupFactory::new()->create(['completed_steps' => ['welcome']]);
    expect(Setup::getCurrentStep())->toBe('school');

    $setup->update(['completed_steps' => ['welcome', 'school']]);
    expect(Setup::getCurrentStep())->toBe('department');

    $setup->update(['completed_steps' => ['welcome', 'school', 'department']]);
    expect(Setup::getCurrentStep())->toBe('complete');
});

it('can check step completed', function () {
    $setup = SetupFactory::new()->create(['completed_steps' => ['welcome']]);

    expect(Setup::isStepCompleted('welcome'))->toBeTrue()
        ->and(Setup::isStepCompleted('school'))->toBeFalse();
});

it('can mark step completed', function () {
    Setup::markStepCompleted('welcome');
    expect(Setup::isStepCompleted('welcome'))->toBeTrue();

    Setup::markStepCompleted('school');
    expect(Setup::isStepCompleted('school'))->toBeTrue();
});

it('does not duplicate completed steps', function () {
    Setup::markStepCompleted('welcome');
    Setup::markStepCompleted('welcome');

    $setup = Setup::first();
    expect($setup->completed_steps)->toBe(['welcome']);
});

it('can store and retrieve created entity', function () {
    $uuid = '550e8400-e29b-41d4-a716-446655440000';

    Setup::storeCreatedEntity('school', $uuid);
    $setup = Setup::first();
    expect($setup->school_id)->toBe($uuid);
    expect(Setup::getCreatedEntity('school'))->toBe($uuid);

    Setup::storeCreatedEntity('department', $uuid);
    $setup->refresh();
    expect($setup->department_id)->toBe($uuid);
    expect(Setup::getCreatedEntity('department'))->toBe($uuid);
});

it('returns null for non-existent entity', function () {
    expect(Setup::getCreatedEntity('school'))->toBeNull();
});
