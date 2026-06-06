<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Actions;

use App\Settings\Support\Settings;
use App\Setup\Actions\ValidateSetupTokenAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Settings::set([
        'setup.install_token' => ['value' => null, 'group' => 'setup', 'type' => 'string'],
        'setup.token_expires_at' => ['value' => null, 'group' => 'setup', 'type' => 'datetime'],
        'setup.is_installed' => ['value' => false, 'group' => 'setup', 'type' => 'boolean'],
        'setup.token_version' => ['value' => 0, 'group' => 'setup', 'type' => 'integer'],
    ]);
});

test('validate setup token action validates and clears valid token', function () {
    $token = 'secure-setup-token';
    Settings::set([
        'setup.install_token' => ['value' => Crypt::encryptString($token), 'group' => 'setup', 'type' => 'string'],
        'setup.token_expires_at' => ['value' => now()->addMinutes(30)->toIso8601String(), 'group' => 'setup', 'type' => 'datetime'],
    ]);

    $action = new ValidateSetupTokenAction;
    $action->execute($token);

    expect(Settings::get('setup.install_token'))->toBeNull();
    expect(Settings::get('setup.token_expires_at'))->toBeNull();
});

test('validate setup token action throws exception if token is missing', function () {
    $action = new ValidateSetupTokenAction;

    expect(fn () => $action->execute('token'))->toThrow(RuntimeException::class, 'Setup token is missing');
});

test('validate setup token action throws exception if token is expired', function () {
    $token = 'expired-token';
    Settings::set([
        'setup.install_token' => ['value' => Crypt::encryptString($token), 'group' => 'setup', 'type' => 'string'],
        'setup.token_expires_at' => ['value' => now()->subMinutes(5)->toIso8601String(), 'group' => 'setup', 'type' => 'datetime'],
    ]);

    $action = new ValidateSetupTokenAction;

    expect(fn () => $action->execute($token))->toThrow(RuntimeException::class, 'Setup token has expired');
});

test('validate setup token action throws exception if token is malformed', function () {
    Settings::set([
        'setup.install_token' => ['value' => 'invalid-not-encrypted-string', 'group' => 'setup', 'type' => 'string'],
        'setup.token_expires_at' => ['value' => now()->addMinutes(30)->toIso8601String(), 'group' => 'setup', 'type' => 'datetime'],
    ]);

    $action = new ValidateSetupTokenAction;

    expect(fn () => $action->execute('token'))->toThrow(RuntimeException::class, 'Setup token is malformed');
});

test('validate setup token action throws exception if token mismatch', function () {
    Settings::set([
        'setup.install_token' => ['value' => Crypt::encryptString('secret-token'), 'group' => 'setup', 'type' => 'string'],
        'setup.token_expires_at' => ['value' => now()->addMinutes(30)->toIso8601String(), 'group' => 'setup', 'type' => 'datetime'],
    ]);

    $action = new ValidateSetupTokenAction;

    expect(fn () => $action->execute('wrong-token'))->toThrow(RuntimeException::class, 'provided setup token does not match');
});
