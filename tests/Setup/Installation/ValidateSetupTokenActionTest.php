<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Settings\Models\Setting;
use App\Modules\Settings\Services\Settings;
use App\Modules\Setup\Domain\Installation\Actions\GenerateSetupTokenAction;
use App\Modules\Setup\Domain\Installation\Actions\ValidateSetupTokenAction;
use App\Modules\Setup\Entities\SetupEntity;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Settings::clearOverrides();
});

test('8NZAU-FR-T4: token is single-use — cleared after successful validation', function () {
    $generated = app(GenerateSetupTokenAction::class)->execute();

    app(ValidateSetupTokenAction::class)->execute($generated->plaintext);

    // Single-use: the stored token is cleared.
    expect(Setting::where('key', 'setup.install_token')->first())->not->toBeNull()
        ->and(Setting::where('key', 'setup.install_token')->first()->value)->toBeNull();
    expect(SetupEntity::get()->hasStoredToken())->toBeFalse();
});

test('8NZAU-NFR-S3: validating the same token twice is rejected as missing', function () {
    $generated = app(GenerateSetupTokenAction::class)->execute();

    app(ValidateSetupTokenAction::class)->execute($generated->plaintext);

    // After first use the token is gone, so a replay attempt sees "missing".
    expect(fn () => app(ValidateSetupTokenAction::class)->execute($generated->plaintext))
        ->toThrow(RejectedException::class, __('setup.token_missing'));
});

test('8NZAU-FR-T3: expired token is rejected', function () {
    $encrypted = Crypt::encryptString('valid-token');
    Settings::override([
        'setup.install_token' => $encrypted,
        'setup.token_expires_at' => now()->subMinutes(10)->toIso8601String(),
    ]);

    expect(fn () => app(ValidateSetupTokenAction::class)->execute('valid-token'))
        ->toThrow(RejectedException::class, __('setup.token_expired'));
});

test('8NZAU-FR-T2: mismatched token is rejected', function () {
    Settings::override([
        'setup.install_token' => Crypt::encryptString('correct-token'),
        'setup.token_expires_at' => now()->addMinutes(30)->toIso8601String(),
    ]);

    expect(fn () => app(ValidateSetupTokenAction::class)->execute('wrong-token'))
        ->toThrow(RejectedException::class, __('setup.token_mismatch'));
});

test('8NZAU-FR-T2: malformed (undecryptable) stored token is rejected', function () {
    Settings::override([
        'setup.install_token' => 'not-a-valid-encrypted-token',
        'setup.token_expires_at' => now()->addMinutes(30)->toIso8601String(),
    ]);

    expect(fn () => app(ValidateSetupTokenAction::class)->execute('anything'))
        ->toThrow(RejectedException::class, __('setup.token_malformed'));
});

test('8NZAU-FR-T4: validating with no stored token at all is rejected as missing', function () {
    expect(fn () => app(ValidateSetupTokenAction::class)->execute('any-token'))
        ->toThrow(RejectedException::class, __('setup.token_missing'));
});
