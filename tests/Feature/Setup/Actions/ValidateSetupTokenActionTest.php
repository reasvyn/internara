<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Actions;

use App\Setup\Actions\ValidateSetupTokenAction;
use App\Setup\Models\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setup::truncate();
});

test('validate setup token action validates and clears valid token', function () {
    $token = 'secure-setup-token';
    $setup = Setup::factory()->create([
        'setup_token' => Crypt::encryptString($token),
        'token_expires_at' => now()->addMinutes(30),
    ]);

    $action = new ValidateSetupTokenAction;
    $action->execute($token);

    $setup->refresh();
    expect($setup->setup_token)->toBeNull();
    expect($setup->token_expires_at)->toBeNull();
});

test('validate setup token action throws exception if no setup record', function () {
    $action = new ValidateSetupTokenAction;

    expect(fn () => $action->execute('token'))->toThrow(RuntimeException::class, 'No setup configuration found');
});

test('validate setup token action throws exception if token is missing', function () {
    Setup::factory()->create([
        'setup_token' => null,
    ]);

    $action = new ValidateSetupTokenAction;

    expect(fn () => $action->execute('token'))->toThrow(RuntimeException::class, 'Setup token is missing');
});

test('validate setup token action throws exception if token is expired', function () {
    $token = 'expired-token';
    Setup::factory()->create([
        'setup_token' => Crypt::encryptString($token),
        'token_expires_at' => now()->subMinutes(5),
    ]);

    $action = new ValidateSetupTokenAction;

    expect(fn () => $action->execute($token))->toThrow(RuntimeException::class, 'Setup token has expired');
});

test('validate setup token action throws exception if token is malformed', function () {
    Setup::factory()->create([
        'setup_token' => 'invalid-not-encrypted-string',
        'token_expires_at' => now()->addMinutes(30),
    ]);

    $action = new ValidateSetupTokenAction;

    expect(fn () => $action->execute('token'))->toThrow(RuntimeException::class, 'Setup token is malformed');
});

test('validate setup token action throws exception if token mismatch', function () {
    Setup::factory()->create([
        'setup_token' => Crypt::encryptString('secret-token'),
        'token_expires_at' => now()->addMinutes(30),
    ]);

    $action = new ValidateSetupTokenAction;

    expect(fn () => $action->execute('wrong-token'))->toThrow(RuntimeException::class, 'provided setup token does not match');
});
