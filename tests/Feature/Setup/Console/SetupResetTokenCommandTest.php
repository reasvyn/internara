<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Console;

use App\Domain\Setup\Models\Setup;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Setup::query()->delete();
});

describe('SetupResetTokenCommand', function () {
    it('rejects reset when installed', function () {
        Setup::factory()->installed()->create();

        $this->artisan('setup:reset-token')
            ->assertFailed();
    });

    it('shows error when installed', function () {
        Setup::factory()->installed()->create();

        $this->artisan('setup:reset-token')
            ->expectsOutputToContain(__('setup.reset_token.protected'));
    });

    it('generates new token when not installed', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->artisan('setup:reset-token')
            ->assertSuccessful();
    });

    it('outputs a setup token on success', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->artisan('setup:reset-token')
            ->assertSuccessful()
            ->expectsOutputToContain(route('setup'));
    });

    it('displays quick access URL on success', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->artisan('setup:reset-token')
            ->assertSuccessful()
            ->expectsOutputToContain(__('setup.cli.quick_access'));
    });

    it('displays manual entry code on success', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->artisan('setup:reset-token')
            ->assertSuccessful()
            ->expectsOutputToContain(__('setup.cli.manual_entry'));
    });

    it('displays token expiration on success', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->artisan('setup:reset-token')
            ->assertSuccessful()
            ->expectsOutputToContain(__('setup.cli.token_expires'));
    });

    it('saves new token to database on success', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->artisan('setup:reset-token')
            ->assertSuccessful();

        $setup = Setup::first();
        expect($setup->setup_token)->not->toBeNull();
        expect($setup->token_expires_at)->not->toBeNull();
    });

    it('can generate multiple tokens sequentially', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->artisan('setup:reset-token')->assertSuccessful();
        $firstToken = Setup::first()->setup_token;

        $this->artisan('setup:reset-token')->assertSuccessful();
        $secondToken = Setup::first()->setup_token;

        expect($secondToken)->not->toBe($firstToken);
    });

    it('displays banner with php version', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->artisan('setup:reset-token')
            ->expectsOutputToContain(__('setup.cli.banner_title'));
    });

    it('displays new token generated section', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->artisan('setup:reset-token')
            ->expectsOutputToContain(__('setup.reset_token.new_token_generated'));
    });

    it('handles empty setups table gracefully', function () {
        $this->artisan('setup:reset-token')
            ->assertSuccessful();
    });
});
