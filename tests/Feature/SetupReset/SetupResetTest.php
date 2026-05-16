<?php

declare(strict_types=1);

use App\Models\Setup;

use function Pest\Laravel\artisan;

beforeEach(function () {
    Setup::query()->forceDelete();
});

it('generates a new token when not installed', function () {
    Setup::factory()->create();

    artisan('setup:reset')
        ->assertSuccessful();

    $setup = Setup::first();
    expect($setup->setup_token)->not->toBeNull();
    expect($setup->token_expires_at)->not->toBeNull();
    expect($setup->is_installed)->toBeFalse();
});

it('refuses to reset when installed', function () {
    Setup::factory()->installed()->create();

    artisan('setup:reset')
        ->assertFailed();
});

it('returns success without --force', function () {
    Setup::factory()->create();

    artisan('setup:reset')
        ->assertSuccessful();

    $setup = Setup::first();
    expect($setup->setup_token)->not->toBeNull();
});
