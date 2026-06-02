<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Console;

use App\Domain\Setup\Models\Setup;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Setup::query()->delete();
});

describe('SetupResetCommand', function () {
    it('rejects reset when installed without --force', function () {
        Setup::factory()->installed()->create();

        $this->artisan('setup:reset')
            ->assertFailed();
    });

    it('shows error when installed without --force', function () {
        Setup::factory()->installed()->create();

        $this->artisan('setup:reset')
            ->expectsOutputToContain(__('setup.reset.protected'));
    });

    it('generates new token when installed with --force', function () {
        Setup::factory()->installed()->create();

        $this->artisan('setup:reset --force')
            ->assertSuccessful();
    });

    it('generates new token when not installed', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->artisan('setup:reset')
            ->assertSuccessful();
    });

    it('outputs a setup token on success', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->artisan('setup:reset')
            ->assertSuccessful()
            ->expectsOutputToContain(route('setup'));
    });

    it('displays quick access URL on success', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->artisan('setup:reset')
            ->assertSuccessful()
            ->expectsOutputToContain(__('setup.cli.quick_access'));
    });

    it('displays manual entry code on success', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->artisan('setup:reset')
            ->assertSuccessful()
            ->expectsOutputToContain(__('setup.cli.manual_entry'));
    });

    it('displays token expiration on success', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->artisan('setup:reset')
            ->assertSuccessful()
            ->expectsOutputToContain(__('setup.cli.token_expires'));
    });

    it('saves new token to database on success', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->artisan('setup:reset')
            ->assertSuccessful();

        $setup = Setup::first();
        expect($setup->setup_token)->not->toBeNull();
        expect($setup->token_expires_at)->not->toBeNull();
    });

    it('can generate multiple tokens sequentially', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->artisan('setup:reset')->assertSuccessful();
        $firstToken = Setup::first()->setup_token;

        $this->artisan('setup:reset')->assertSuccessful();
        $secondToken = Setup::first()->setup_token;

        expect($secondToken)->not->toBe($firstToken);
    });

    it('displays banner with php version', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->artisan('setup:reset')
            ->expectsOutputToContain(__('setup.cli.banner_title'));
    });

    it('displays new token generated section', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->artisan('setup:reset')
            ->expectsOutputToContain(__('setup.reset.new_token_generated'));
    });

    it('handles empty setups table gracefully', function () {
        $this->artisan('setup:reset')
            ->assertSuccessful();
    });
});
