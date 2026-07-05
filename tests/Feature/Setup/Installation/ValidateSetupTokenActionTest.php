<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Installation\Actions;

use Tests\Support\WithSettingsSeed;
use App\Setup\Installation\Actions\ValidateSetupTokenAction;
use App\Settings\Services\Settings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

uses(LazilyRefreshDatabase::class);
uses(WithSettingsSeed::class);

beforeEach(function () {
    $this->seedSettings([
        'setup.install_token' => ['value' => null, 'group' => 'setup', 'type' => 'string'],
        'setup.token_expires_at' => ['value' => null, 'group' => 'setup', 'type' => 'datetime'],
        'setup.is_installed' => ['value' => false, 'group' => 'setup', 'type' => 'boolean'],
        'setup.token_version' => ['value' => 0, 'group' => 'setup', 'type' => 'integer'],
    ]);
});

test('validate setup token action validates and clears valid token', function () {
    $token = 'secure-setup-token';
    $this->seedSettings([
        'setup.install_token' => [
            'value' => Crypt::encryptString($token),
            'group' => 'setup',
            'type' => 'string',
        ],
        'setup.token_expires_at' => [
            'value' => now()->addMinutes(30)->toIso8601String(),
            'group' => 'setup',
            'type' => 'datetime',
        ],
    ]);

    $action = app(ValidateSetupTokenAction::class);
    $action->execute($token);

    expect(Settings::get('setup.install_token'))->toBeNull();
    expect(Settings::get('setup.token_expires_at'))->toBeNull();
});

test('validate setup token action throws exception if token is missing', function () {
    $action = app(ValidateSetupTokenAction::class);

    expect(fn() => $action->execute('token'))->toThrow(
        RuntimeException::class,
        'Setup token is missing',
    );
});

test('validate setup token action throws exception if token is expired', function () {
    $token = 'expired-token';
    $this->seedSettings([
        'setup.install_token' => [
            'value' => Crypt::encryptString($token),
            'group' => 'setup',
            'type' => 'string',
        ],
        'setup.token_expires_at' => [
            'value' => now()->subMinutes(5)->toIso8601String(),
            'group' => 'setup',
            'type' => 'datetime',
        ],
    ]);

    $action = app(ValidateSetupTokenAction::class);

    expect(fn() => $action->execute($token))->toThrow(
        RuntimeException::class,
        'Setup token has expired',
    );
});

test('validate setup token action throws exception if token is malformed', function () {
    $this->seedSettings([
        'setup.install_token' => [
            'value' => 'invalid-not-encrypted-string',
            'group' => 'setup',
            'type' => 'string',
        ],
        'setup.token_expires_at' => [
            'value' => now()->addMinutes(30)->toIso8601String(),
            'group' => 'setup',
            'type' => 'datetime',
        ],
    ]);

    $action = app(ValidateSetupTokenAction::class);

    expect(fn() => $action->execute('token'))->toThrow(
        RuntimeException::class,
        'Setup token is malformed',
    );
});

test('validate setup token action throws exception if token mismatch', function () {
    $this->seedSettings([
        'setup.install_token' => [
            'value' => Crypt::encryptString('secret-token'),
            'group' => 'setup',
            'type' => 'string',
        ],
        'setup.token_expires_at' => [
            'value' => now()->addMinutes(30)->toIso8601String(),
            'group' => 'setup',
            'type' => 'datetime',
        ],
    ]);

    $action = app(ValidateSetupTokenAction::class);

    expect(fn() => $action->execute('wrong-token'))->toThrow(
        RuntimeException::class,
        'provided setup token does not match',
    );
});
