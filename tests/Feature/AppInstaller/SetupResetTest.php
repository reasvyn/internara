<?php

declare(strict_types=1);

use App\Models\Setup;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\artisan;

beforeEach(function () {
    if (File::exists(base_path('.installed'))) {
        File::delete(base_path('.installed'));
    }
});

afterEach(function () {
    if (File::exists(base_path('.installed'))) {
        File::delete(base_path('.installed'));
    }
});

it('generates a new token when not installed and --force is used', function () {
    Setup::factory()->create();

    artisan('setup:reset', ['--force' => true])
        ->assertSuccessful();

    $setup = Setup::first();
    expect($setup->setup_token)->not->toBeNull();
    expect($setup->token_expires_at)->not->toBeNull();
    expect($setup->is_installed)->toBeFalse();
});

it('resets state and generates token when installed and --force is used', function () {
    Setup::factory()->installed()->create();
    File::put(base_path('.installed'), now()->toDateTimeString());

    artisan('setup:reset', ['--force' => true])
        ->assertSuccessful();

    $setup = Setup::first();
    expect($setup->is_installed)->toBeFalse();
    expect($setup->completed_steps)->toBe([]);
    expect($setup->setup_token)->not->toBeNull();
    expect($setup->token_expires_at)->not->toBeNull();
    expect(File::exists(base_path('.installed')))->toBeFalse();
});

it('returns success when not installed without --force', function () {
    Setup::factory()->create();

    artisan('setup:reset')
        ->assertSuccessful();

    $setup = Setup::first();
    expect($setup->setup_token)->not->toBeNull();
});
