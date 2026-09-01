<?php

declare(strict_types=1);

use App\Modules\Settings\Data\SettingEntryData;
use App\Modules\Settings\Services\Settings;
use App\Modules\Setup\Entities\SetupEntity;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Settings::clearOverrides();
});

test('8NZAU-FR-T1: hasStoredToken is false when no token is stored', function () {
    $state = SetupEntity::get();

    expect($state->hasStoredToken())->toBeFalse();
});

test('8NZAU-NFR-S3: hasStoredToken is true when a token is stored', function () {
    Settings::override(['setup.install_token' => 'some-token']);

    $state = SetupEntity::get();

    expect($state->hasStoredToken())->toBeTrue();
});

test('8NZAU-FR-T3: isTokenExpired returns true for a past expiry and false for a future one', function () {
    Settings::override(['setup.token_expires_at' => now()->subMinutes(10)->toIso8601String()]);
    expect(SetupEntity::get()->isTokenExpired(now()))->toBeTrue();

    Settings::override(['setup.token_expires_at' => now()->addMinutes(30)->toIso8601String()]);
    expect(SetupEntity::get()->isTokenExpired(now()))->toBeFalse();
});

test('8NZAU-FR-T3: an absent expiry timestamp is treated as expired', function () {
    $state = SetupEntity::get();

    expect($state->isTokenExpired(now()))->toBeTrue();
});

test('8NZAU-FR-T3: validateToken returns false when the stored token is expired', function () {
    Settings::override(['setup.token_expires_at' => now()->subMinutes(5)->toIso8601String()]);
    $state = SetupEntity::get();

    expect($state->validateToken('stored', 'stored', now()))->toBeFalse();
});

test('8NZAU-FR-T3: validateToken returns false on hash mismatch', function () {
    Settings::override(['setup.token_expires_at' => now()->addMinutes(30)->toIso8601String()]);
    $state = SetupEntity::get();

    expect($state->validateToken('stored-token', 'input-differs', now()))->toBeFalse();
});

test('8NZAU-FR-T3: validateToken returns true on matching, non-expired token', function () {
    Settings::override(['setup.token_expires_at' => now()->addMinutes(30)->toIso8601String()]);
    $state = SetupEntity::get();

    expect($state->validateToken('stored-token', 'stored-token', now()))->toBeTrue();
});

test('8NZAU-FR-W1: isStepCompleted reports completed wizard steps', function () {
    Settings::override(['setup.completed_steps' => ['welcome', 'account']]);
    $state = SetupEntity::get();

    expect($state->isStepCompleted('welcome'))->toBeTrue();
    expect($state->isStepCompleted('account'))->toBeTrue();
    expect($state->isStepCompleted('school'))->toBeFalse();
});

test('8NZAU-FR-W1: allStepsCompleted is false until every configured step is done', function () {
    $state = SetupEntity::get();

    expect($state->allStepsCompleted())->toBeFalse();

    Settings::override([
        'setup.completed_steps' => ['welcome', 'account', 'school', 'department', 'finalize'],
    ]);
    expect(SetupEntity::get()->allStepsCompleted())->toBeFalse();
});

test('8NZAU-FR-W1: allStepsCompleted is true when all configured steps are done', function () {
    Settings::override([
        'setup.completed_steps' => config('setup.wizard.step_keys'),
    ]);

    expect(SetupEntity::get()->allStepsCompleted())->toBeTrue();
});

test('8NZAU-FR-W1: hasRecoveryKey reflects the presence of a recovery key', function () {
    expect(SetupEntity::get()->hasRecoveryKey())->toBeFalse();

    Settings::override(['setup.install_recovery_key' => 'hashed-key']);
    expect(SetupEntity::get()->hasRecoveryKey())->toBeTrue();
});

test('8NZAU-FR-T5: tokenVersion returns the stored version integer', function () {
    expect(SetupEntity::get()->tokenVersion())->toBe(0);

    Settings::override(['setup.token_version' => 7]);
    expect(SetupEntity::get()->tokenVersion())->toBe(7);
});

test('8NZAU-FR-S7: toSettingsEntries maps declared keys to their configured types', function () {
    $entries = SetupEntity::toSettingsEntries([
        'is_installed' => true,
        'completed_steps' => ['welcome'],
        'token_version' => 3,
        'token_expires_at' => '2026-01-01T00:00:00+00:00',
    ]);

    $byKey = collect($entries)->keyBy(fn (SettingEntryData $e) => $e->key);

    expect($byKey['setup.is_installed']->type)->toBe('boolean');
    expect($byKey['setup.completed_steps']->type)->toBe('json');
    expect($byKey['setup.token_version']->type)->toBe('integer');
    expect($byKey['setup.token_expires_at']->type)->toBe('datetime');
});

test('8NZAU-FR-S7: toSettingsEntries detects types for undeclared keys from their value', function () {
    $entries = SetupEntity::toSettingsEntries([
        'some_bool' => true,
        'some_array' => [1, 2, 3],
        'some_int' => 5,
        'some_string' => 'text',
    ]);

    $byKey = collect($entries)->keyBy(fn (SettingEntryData $e) => $e->key);

    expect($byKey['setup.some_bool']->type)->toBe('boolean');
    expect($byKey['setup.some_array']->type)->toBe('json');
    expect($byKey['setup.some_int']->type)->toBe('integer');
    expect($byKey['setup.some_string']->type)->toBe('string');
});

test('8NZAU-FR-S7: toSettingsEntries prefixes keys with setup and sets the setup group', function () {
    $entries = SetupEntity::toSettingsEntries(['some_bool' => true]);

    expect($entries)->toHaveCount(1);
    expect($entries[0]->key)->toBe('setup.some_bool');
    expect($entries[0]->group)->toBe('setup');
});
