<?php

declare(strict_types=1);

use App\Models\Setup;

use function Pest\Laravel\artisan;

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

    artisan('setup:reset', ['--force' => true])
        ->assertSuccessful();

    $setup = Setup::first();
    expect($setup->is_installed)->toBeFalse();
    expect($setup->completed_steps)->toBe([]);
    expect($setup->setup_token)->not->toBeNull();
    expect($setup->token_expires_at)->not->toBeNull();
});

it('returns success when not installed without --force', function () {
    Setup::factory()->create();

    artisan('setup:reset')
        ->assertSuccessful();

    $setup = Setup::first();
    expect($setup->setup_token)->not->toBeNull();
});
