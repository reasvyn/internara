<?php

declare(strict_types=1);

use App\Modules\Settings\Models\Setting;
use App\Modules\Settings\Services\Settings;
use App\Modules\Setup\Domain\Installation\Actions\GenerateSetupTokenAction;
use App\Modules\Setup\Entities\SetupEntity;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Settings::clearOverrides();
});

test('8NZAU-FR-T1: token is a 64-character cryptographically random string', function () {
    $result = app(GenerateSetupTokenAction::class)->execute();

    expect(strlen($result->plaintext))->toBe(64);
    expect($result->plaintext)->toBeString();
    // Cryptographically random => generating again must not yield the same token (NFR-S1).
    $second = app(GenerateSetupTokenAction::class)->execute();
    expect($second->plaintext)->not->toBe($result->plaintext);
});

test('8NZAU-NFR-S1: token is 64 characters and differs across generations', function () {
    $first = app(GenerateSetupTokenAction::class)->execute();
    $second = app(GenerateSetupTokenAction::class)->execute();

    expect(strlen($first->plaintext))->toBe(64);
    expect(strlen($second->plaintext))->toBe(64);
    expect($second->plaintext)->not->toBe($first->plaintext);
});

test('8NZAU-FR-T2: token is encrypted at rest and stored value differs from plaintext', function () {
    $result = app(GenerateSetupTokenAction::class)->execute();

    $stored = Setting::where('key', 'setup.install_token')->first()->value;

    // Encrypted at rest: the persisted string is not the readable plaintext.
    expect($stored)->not->toBe($result->plaintext);
    // And it round-trips back to the plaintext when decrypted.
    expect(Crypt::decryptString($stored))->toBe($result->plaintext);
});

test('8NZAU-FR-T3: token expires approximately 60 minutes after generation', function () {
    $result = app(GenerateSetupTokenAction::class)->execute();

    $expected = now()->addMinutes((int) config('setup.token.expiry_minutes', 60));
    expect($result->expiresAt->diffInSeconds($expected))->toBeLessThanOrEqual(5);

    // The stored expiry timestamp matches the returned one.
    $stored = Setting::where('key', 'setup.token_expires_at')->first();
    expect($stored)->not->toBeNull()
        ->and(Carbon\Carbon::parse($stored->value)->diffInSeconds($result->expiresAt))->toBeLessThanOrEqual(2);
});

test('8NZAU-FR-T5: token version increments on each generation', function () {
    app(GenerateSetupTokenAction::class)->execute();
    $firstVersion = (int) Setting::where('key', 'setup.token_version')->first()->value;

    app(GenerateSetupTokenAction::class)->execute();
    $secondVersion = (int) Setting::where('key', 'setup.token_version')->first()->value;

    expect($firstVersion)->toBe(1);
    expect($secondVersion)->toBe($firstVersion + 1);
});

test('8NZAU-FR-T5: a stored token state is reflected by SetupEntity::tokenVersion', function () {
    app(GenerateSetupTokenAction::class)->execute();

    $state = SetupEntity::get();

    expect($state->tokenVersion())->toBe((int) Setting::where('key', 'setup.token_version')->first()->value);
});
