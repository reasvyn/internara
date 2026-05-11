<?php

declare(strict_types=1);

use App\Models\Setup;
use Database\Factories\SetupFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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
    expect(Setup::state()->isInstalled())->toBeFalse();
});

it('detects installed via database', function () {
    SetupFactory::new()->installed()->create();
    expect(Setup::state()->isInstalled())->toBeTrue();
});

it('detects installed via file', function () {
    File::put(base_path('.installed'), now()->toDateTimeString());
    expect(Setup::state()->isInstalled())->toBeTrue();
});

it('can mark as installed', function () {
    $setup = Setup::firstOrCreate([]);
    $setup->update(['is_installed' => true]);
    File::put(base_path('.installed'), now()->toDateTimeString());
    expect(Setup::state()->isInstalled())->toBeTrue()
        ->and(File::exists(base_path('.installed')))->toBeTrue();
});

it('can generate token', function () {
    $plaintext = Str::random(64);
    $expiresAt = now()->addHour();
    $encrypted = Crypt::encryptString($plaintext);
    $setup = Setup::firstOrCreate([]);
    $setup->update(['setup_token' => $encrypted, 'token_expires_at' => $expiresAt]);

    expect($setup->fresh()->setup_token)->not->toBeNull()
        ->and($setup->fresh()->token_expires_at)->not->toBeNull();
});

it('can validate correct token', function () {
    $plaintext = Str::random(64);
    $encrypted = Crypt::encryptString($plaintext);
    $setup = Setup::firstOrCreate([]);
    $setup->update(['setup_token' => $encrypted, 'token_expires_at' => now()->addHour()]);
    expect(Setup::state()->validateToken($plaintext, $plaintext, now()))->toBeTrue();
});

it('rejects invalid token', function () {
    $plaintext = Str::random(64);
    $encrypted = Crypt::encryptString($plaintext);
    $setup = Setup::firstOrCreate([]);
    $setup->update(['setup_token' => $encrypted, 'token_expires_at' => now()->addHour()]);
    expect(Setup::state()->validateToken($plaintext, 'invalid-token', now()))->toBeFalse();
});

it('rejects expired token', function () {
    $plaintext = Str::random(64);
    $encrypted = Crypt::encryptString($plaintext);
    $setup = Setup::firstOrCreate([]);
    $setup->update(['setup_token' => $encrypted, 'token_expires_at' => now()->subHour()]);

    expect(Setup::state()->validateToken($plaintext, $plaintext, now()))->toBeFalse();
});

it('can invalidate token', function () {
    $plaintext = Str::random(64);
    $encrypted = Crypt::encryptString($plaintext);
    $setup = Setup::firstOrCreate([]);
    $setup->update(['setup_token' => $encrypted, 'token_expires_at' => now()->addHour()]);
    $setup->update(['setup_token' => null, 'token_expires_at' => null]);

    expect($setup->fresh()->setup_token)->toBeNull()
        ->and($setup->fresh()->token_expires_at)->toBeNull();
});

it('can check step completed', function () {
    $setup = SetupFactory::new()->create(['completed_steps' => ['welcome']]);

    expect(Setup::state()->isStepCompleted('welcome'))->toBeTrue()
        ->and(Setup::state()->isStepCompleted('school'))->toBeFalse();
});

it('can mark step completed', function () {
    $setup = Setup::firstOrCreate([]);
    $steps = $setup->completed_steps ?? [];
    $steps[] = 'welcome';
    $setup->update(['completed_steps' => $steps]);
    expect(Setup::state()->isStepCompleted('welcome'))->toBeTrue();
});

it('does not duplicate completed steps', function () {
    $setup = Setup::firstOrCreate([]);
    $steps = $setup->completed_steps ?? [];
    foreach (['welcome', 'welcome'] as $s) {
        if (! in_array($s, $steps)) {
            $steps[] = $s;
        }
    }
    $setup->update(['completed_steps' => $steps]);

    $setup = Setup::first();
    expect($setup->completed_steps)->toBe(['welcome']);
});

it('can store and retrieve created entity', function () {
    $uuid = '550e8400-e29b-41d4-a716-446655440000';
    $setup = Setup::firstOrCreate([]);

    $setup->update(['school_id' => $uuid]);
    expect($setup->fresh()->school_id)->toBe($uuid);

    $setup->update(['department_id' => $uuid]);
    expect($setup->fresh()->department_id)->toBe($uuid);
});
